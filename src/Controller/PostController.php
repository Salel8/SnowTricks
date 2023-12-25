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
use App\Controller\CommentController;
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

class PostController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/posts/new', name: 'page_add_post')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator, NotifierInterface $notifier): Response
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
                    $originalPictureFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safePictureFilename = $slugger->slug($originalPictureFilename);
                    $newPictureFilename = $safePictureFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                    // Move the file to the directory where pictures are stored
                    try {
                        $pictureFile->move(
                            $this->getParameter('pictures_directory'),
                            $newPictureFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    $picture = new Picture_post();
                    // updates the 'pictureFilename' property to store the PDF file name
                    // instead of its contents
                    $picture->setPictureFilename($newPictureFilename);
                    $picture->setIdPost($post);

                    $errors = $validator->validate($picture);
                    if (count($errors) > 0) {
                        return new Response((string) $errors, 400);
                    }

                    $entityManager->persist($picture);
                    $entityManager->flush();
                }
            }

            /** @var UploadedFile $videoFile */
            $videoFiles = $form->get('video')->getData();

            if ($videoFiles) {
                $video = new Video_post();
                $video->setVideoFilename($videoFiles);
                $video->setIdPost($post);

                $errors = $validator->validate($video);
                if (count($errors) > 0) {
                    return new Response((string) $errors, 400);
                }
                $entityManager->persist($video);
                $entityManager->flush();
            }

            return $this->redirectToRoute('all_posts');
        }

            return $this->render('new.html.twig', array(
                'form' => $form->createView(),
                //'form_video' => $form_video->createView(),
                //'form_picture' => $form_picture->createView(),
            ));
        }



    #[Route('/posts', name: 'all_posts')]
    public function getLastPosts(EntityManagerInterface $entityManager, Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 8);

        $qb = $entityManager->createQueryBuilder()
            ->from('App\Entity\Post', 'p')
            ->select('p')
            //->setParameter('val', $id)
            //->andwhere('p.id = :val')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $posts = $qb->getQuery()->getResult();

        $nbposts = count($entityManager->getRepository(Post::class)->findAll());

        if (($nbposts % $limit) > 0){
            $nbpage = intdiv($nbposts, $limit) + 1;
        } else{
            $nbpage = intdiv($nbposts, $limit);
        }

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
            'page' => $page,
            'nbpage' => $nbpage,
        ]);
    }

    #[Route('/post/{slug}', name: 'post_show')]
    public function show(string $slug, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator): Response
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


        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setDate(new \DateTimeImmutable());
            $comment->setIdPost($post);

            $errors = $validator->validate($comment);
            if (count($errors) > 0) {
                return new Response((string) $errors, 400);
            }

            $entityManager->persist($comment);
            $entityManager->flush();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 2);

        $qb = $entityManager->createQueryBuilder()
            ->from('App\Entity\Comment', 'c')
            ->select('c')
            ->setParameter('val', $post->getId())
            ->andwhere('c.id_post = :val')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $comments = $qb->getQuery()->getResult();

        if ((count($post->getComments()) % $limit) > 0){
            $nbpage = intdiv(count($post->getComments()), $limit) + 1;
        } else{
            $nbpage = intdiv(count($post->getComments()), $limit);
        }

        function get_gravatar_url( $email ) {
            // Trim leading and trailing whitespace from
            // an email address and force all characters
            // to lower case
            $address = strtolower( trim( $email ) );
          
            // Create an SHA256 hash of the final string
            $hash = hash( 'sha256', $address );
          
            // Grab the actual image URL
            return 'https://www.gravatar.com/avatar/' . $hash;
        }

        $gravatar=[];
        foreach($comments as $comment){
            $gravatar[$comment->getId()] = get_gravatar_url( $comment->getEmail() );
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
            'page' => $page,
            'nbpage' => $nbpage,
            'gravatar' => $gravatar,
            'date_jour' => $date_jour,
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/post/edit/{slug}', name: 'post_edit')]
    public function update(string $slug, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator): Response
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
                    $originalPictureFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safePictureFilename = $slugger->slug($originalPictureFilename);
                    $newPictureFilename = $safePictureFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                    // Move the file to the directory where pictures are stored
                    try {
                        $pictureFile->move(
                            $this->getParameter('pictures_directory'),
                            $newPictureFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    $picture = new Picture_post();
                    // updates the 'pictureFilename' property to store the PDF file name
                    // instead of its contents
                    $picture->setPictureFilename($newPictureFilename);
                    $picture->setIdPost($post_db);

                    $errors = $validator->validate($picture);
                    if (count($errors) > 0) {
                        return new Response((string) $errors, 400);
                    }

                    $entityManager->persist($picture);
                    $entityManager->flush();
                }
            }

            /** @var UploadedFile $videoFile */
            $videoFiles = $form->get('video')->getData();

            if ($videoFiles) {
                $video = new Video_post();
                $video->setVideoFilename($videoFiles);
                $video->setIdPost($post_db);

                $errors = $validator->validate($video);
                if (count($errors) > 0) {
                    return new Response((string) $errors, 400);
                }
                $entityManager->persist($video);
                $entityManager->flush();
            }


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
    public function updatePicture(int $id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator): Response
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
            //$pictureFile = $form->getData();
            $pictureFile = $form->get('picture')->getData();

            // this condition is needed because the 'video' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $originalPictureFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safePictureFilename = $slugger->slug($originalPictureFilename);
                $newPictureFilename = $safePictureFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                // Move the file to the directory where pictures are stored
                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newPictureFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'videoFilename' property to store the PDF file name
                // instead of its contents
                $picture->setPictureFilename($newPictureFilename);
            }

            $picture_db->setPictureFilename($newPictureFilename);

            $errors = $validator->validate($picture_db);
            if (count($errors) > 0) {
                return new Response((string) $errors, 400);
            }

            $entityManager->persist($picture_db);
            $entityManager->flush();

    
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
    public function updateVideo(int $id, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
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
                
                $video_db->setVideoFilename($videoFile);

                $errors = $validator->validate($video_db);
                if (count($errors) > 0) {
                    return new Response((string) $errors, 400);
                }

                $entityManager->persist($video_db);
                $entityManager->flush();

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
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $pictures = $entityManager->getRepository(Picture_post::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );
        if ($pictures) {
            foreach($pictures as $picture){
                unlink($this->getParameter('pictures_directory').'/'.$picture->getPictureFilename());
                $entityManager->remove($picture);
                $entityManager->flush();
            }
        }

        $videos = $entityManager->getRepository(Video_post::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );
        if ($videos) {
            foreach($videos as $video){
                $entityManager->remove($video);
                $entityManager->flush();
            }
        }

        $comments = $entityManager->getRepository(Comment::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );
        if ($comments) {
            foreach($comments as $comment){
                $entityManager->remove($comment);
                $entityManager->flush();
            }
        }


        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException(
                'No post found for id '.$id
            );
        }

        $entityManager->remove($post);
        $entityManager->flush();


        

        return $this->redirectToRoute('all_posts');
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/picture/delete/{id}', name: 'picture_delete')]
    public function deletePicture(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $picture = $entityManager->getRepository(Picture_post::class)->find($id);

        if (!$picture) {
            throw $this->createNotFoundException(
                'No picture found for id '.$id
            );
        }

        $post_name = $request->get('postname', 'vide');

        unlink($this->getParameter('pictures_directory').'/'.$picture->getPictureFilename());

        $entityManager->remove($picture);
        $entityManager->flush();

        return $this->redirectToRoute('post_show', [
            'slug' => $post_name
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/video/delete/{id}', name: 'video_delete')]
    public function deleteVideo(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $video = $entityManager->getRepository(Video_post::class)->find($id);

        if (!$video) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }

        $post_name = $request->get('postname', 'vide');

        $entityManager->remove($video);
        $entityManager->flush();

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