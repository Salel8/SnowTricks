<?php
// src/Controller/Post.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\PostType;
use App\Entity\Post;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Video_post;
use App\Form\VideoType;
use App\Entity\Picture_post;
use App\Form\PictureType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Form\CommentType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use App\Service\MailEnvoi;
use App\Controller\CommentController;
use App\Repository\CommentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\FileUploader;

class PostController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/posts/new', name: 'page_add_post')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator, NotifierInterface $notifier, FileUploader $fileUploader): Response
    {
        $post = new Post();

        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            $errors = $validator->validate($post);
            if (count($errors) > 0) {
                return new Response((string) $errors, 400);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            $notifier->send(new Notification('Vous avez créé un nouvel article.', ['browser']));

            /** @var UploadedFile $pictureFile */
            $pictureFiles = $form->get('picture')->getData();

            foreach ($pictureFiles as $pictureFile) {
                // this condition is needed because the 'picture' field is not required
                // so the PDF file must be processed only when a file is uploaded
                if ($pictureFile) {
                    $fileUploader->uploadPicture($post, $pictureFile, $entityManager, $slugger, $validator);
                }
            }

            /** @var UploadedFile $videoFile */
            $videoFiles = $form->get('video')->getData();

            if ($videoFiles) {
                $fileUploader->uploadVideo($videoFiles, $post, $entityManager, $validator);
            }

            return $this->redirectToRoute('all_posts');
        }

            return $this->render('new.html.twig', array(
                'form' => $form->createView(),
            ));
        }



    #[Route('/posts', name: 'all_posts')]
    public function getLastPosts(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAllForPagination();


        $pagination = $paginator->paginate(
            $posts,
            $request->query->get('page', 1),
            8
        );

        $pictures=[];
        foreach ($posts as $post) {
            $picture = $entityManager->getRepository(Picture_post::class)->findOneBy(['id_post' => $post->getId()]);
            if (null!==$picture){
                $pictures[$post->getId()] = $picture->getPictureFilename();
            }
        }

        return $this->render('accueil.html.twig', [
            'posts' => $posts,
            'pictures' => $pictures,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/post/{slug}', name: 'post_show')]
    public function show(string $slug, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator, CommentController $commentController, PaginatorInterface $paginator, CommentRepository $commentRepository): Response
    {
        $post = $entityManager->getRepository(Post::class)->findOneBy(['name' => $slug]);

        if (!$post) {
            throw $this->createNotFoundException(
                'No post found for name '.$slug
            );
        }

        $pictures = $entityManager->getRepository(Picture_post::class)->findBy(
            ['id_post' => $post->getId()],
            ['id' => 'ASC']
        );

        $videos = $entityManager->getRepository(Video_post::class)->findBy(
            ['id_post' => $post->getId()],
            ['id' => 'ASC']
        );

        $form = $commentController->newComment($post, $request, $entityManager, $validator);

        $comments = $commentRepository->findAllForPagination($post);

        $pagination = $paginator->paginate(
            $comments,
            $request->query->get('page', 1),
            2
        );
        
  
        $gravatar=[];
        foreach($comments as $comment){
            $gravatar[$comment->getId()] = $commentController->get_gravatar_url( $comment->getEmail() );
        }
       

        $date = new \DateTime();
        $date_jour = $date->format('\l\e d/m/Y');


        return $this->render('detail.html.twig', [
            'post_group_figure' => $post->getGroupFigure(),
            'post' => $post,
            'pictures' => $pictures,
            'videos' => $videos,
            'comments' => $comments,
            'form' => $form->createView(),
            'gravatar' => $gravatar,
            'date_jour' => $date_jour,
            'pagination' => $pagination,
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/post/edit/{slug}', name: 'post_edit')]
    public function update(string $slug, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator, NotifierInterface $notifier, FileUploader $fileUploader): Response
    {
        $post_db = $entityManager->getRepository(Post::class)->findOneBy(['name' => $slug]);
        $pictures_db = $entityManager->getRepository(Picture_post::class)->findBy(
            ['id_post' => $post_db->getId()]
        );
        $videos_db = $entityManager->getRepository(Video_post::class)->findBy(
            ['id_post' => $post_db->getId()]
        );

        if (!$post_db) {
            throw $this->createNotFoundException(
                'No post found for name '.$slug
            );
        }

        $post = new Post();
        $post->setName($post_db->getName());
        $post->setDescription($post_db->getDescription());
        $post->setGroupFigure($post_db->getGroupFigure());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $post = $form->getData();

            $post_db->setName($post->getName());
            $post_db->setDescription($post->getDescription());
            $post_db->setGroupFigure($post->getGroupFigure());

            $errors = $validator->validate($post_db);
            if (count($errors) > 0) {
                return new Response((string) $errors, 400);
            }

            $entityManager->persist($post_db);
            $entityManager->flush();

            /** @var UploadedFile $pictureFile */
            $pictureFiles = $form->get('picture')->getData();

            foreach ($pictureFiles as $pictureFile) {
                // this condition is needed because the 'picture' field is not required
                // so the PDF file must be processed only when a file is uploaded
                if ($pictureFile) {
                    $fileUploader->uploadPicture($post_db, $pictureFile, $entityManager, $slugger, $validator);
                }
            }

            /** @var UploadedFile $videoFile */
            $videoFiles = $form->get('video')->getData();

            if ($videoFiles) {
                $fileUploader->uploadVideo($videoFiles, $post, $entityManager, $validator);
            }

            $notifier->send(new Notification('Vous avez modifié votre article.', ['browser']));


            return $this->redirectToRoute('post_show', [
                'slug' => $post_db->getName()
            ]);

        }

        return $this->render('edit.html.twig', array(
            'form' => $form->createView(),
            'post' => $post_db,
            'pictures' => $pictures_db,
            'videos' => $videos_db
        ));
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/picture/edit/{id}', name: 'picture_edit')]
    public function updatePicture(int $id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator, FileUploader $fileUploader): Response
    {
        $picture_db = $entityManager->getRepository(Picture_post::class)->find($id);

        if (!$picture_db) {
            throw $this->createNotFoundException(
                'No picture found for id '.$id
            );
        }

        $post_name = $request->get('postname', 'vide');

        $picture = new Picture_post();
        

        $form = $this->createForm(PictureType::class, $picture);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            unlink($this->getParameter('pictures_directory').'/'.$picture_db->getPictureFilename());

            /** @var UploadedFile $pictureFile */
            $pictureFile = $form->get('picture')->getData();

            // this condition is needed because the 'video' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $fileUploader->editPicture($picture_db, $pictureFile, $entityManager, $slugger, $validator);
            }
    
            return $this->redirectToRoute('post_edit', [
                'slug' => $post_name
            ]);

        }

        return $this->render('editPicture.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/video/edit/{id}', name: 'video_edit')]
    public function updateVideo(int $id, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, FileUploader $fileUploader): Response
    {
        $video_db = $entityManager->getRepository(Video_post::class)->find($id);

        if (!$video_db) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }

        $post_name = $request->get('postname', 'vide');

        $video = new Video_post();
        

        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            

            /** @var UploadedFile $pictureFile */
            $videoFile = $form->get('video')->getData();

            // this condition is needed because the 'video' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($videoFile) {

                $fileUploader->editVideo($video_db, $videoFile, $entityManager, $validator);

                return $this->redirectToRoute('post_show', [
                    'slug' => $post_name
                ]);
            }

        }

        return $this->render('editVideo.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/post/delete/{id}', name: 'post_delete')]
    public function delete(int $id, EntityManagerInterface $entityManager, CommentController $commentController, NotifierInterface $notifier, FileUploader $fileUploader): Response
    {
        $pictures = $entityManager->getRepository(Picture_post::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );
        if ($pictures) {
            foreach($pictures as $picture){
                unlink($this->getParameter('pictures_directory').'/'.$picture->getPictureFilename());
                $fileUploader->deleteFile($picture, $entityManager);
            }
        }

        $videos = $entityManager->getRepository(Video_post::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );
        if ($videos) {
            foreach($videos as $video){
                $fileUploader->deleteFile($video, $entityManager);
            }
        }

        $commentController->deleteComment($id, $entityManager);


        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException(
                'No post found for id '.$id
            );
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $notifier->send(new Notification("Vous avez supprimé l'article.", ['browser']));
        

        return $this->redirectToRoute('all_posts');
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/picture/delete/{id}', name: 'picture_delete')]
    public function deletePicture(int $id, EntityManagerInterface $entityManager, Request $request, FileUploader $fileUploader): Response
    {
        $picture = $entityManager->getRepository(Picture_post::class)->find($id);

        if (!$picture) {
            throw $this->createNotFoundException(
                'No picture found for id '.$id
            );
        }

        $post_name = $request->get('postname', 'vide');

        unlink($this->getParameter('pictures_directory').'/'.$picture->getPictureFilename());

        $fileUploader->deleteFile($picture, $entityManager);

        return $this->redirectToRoute('post_show', [
            'slug' => $post_name
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/video/delete/{id}', name: 'video_delete')]
    public function deleteVideo(int $id, EntityManagerInterface $entityManager, Request $request, FileUploader $fileUploader): Response
    {
        $video = $entityManager->getRepository(Video_post::class)->find($id);

        if (!$video) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }

        $post_name = $request->get('postname', 'vide');

        $fileUploader->deleteFile($video, $entityManager);

        return $this->redirectToRoute('post_show', [
            'slug' => $post_name
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/delete', name: 'delete_choice')]
    public function deleteChoice(EntityManagerInterface $entityManager, Request $request): Response
    {
        $post_name = $request->get('postname', 'vide');

        $post_id = $request->get('postid', 'vide');

        $picture_id = $request->get('pictureid', 'vide');

        $video_id = $request->get('videoid', 'vide');

        $redirection = $request->get('redirection', 'vide');

        $choice = $request->get('choice', 'vide');

        return $this->render('delete.html.twig', array(
            'post_name' => $post_name,
            'post_id' => $post_id,
            'picture_id' => $picture_id,
            'video_id' => $video_id,
            'redirection' => $redirection,
            'choice' => $choice
        ));
    }
}