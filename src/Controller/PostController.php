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

class PostController extends AbstractController
{
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/posts/new', name: 'page_add_post')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $post = new Post();

        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $entityManager->persist($post);
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
                    $picture->setIdPost($post);
                    $entityManager->persist($picture);
                    $entityManager->flush();
                }
            }

            /** @var UploadedFile $videoFile */
            $videoFiles = $form->get('video')->getData();

            foreach($videoFiles as $videoFile){
                // this condition is needed because the 'video' field is not required
                // so the PDF file must be processed only when a file is uploaded
                if ($videoFile) {
                    $originalVideoFilename = pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeVideoFilename = $slugger->slug($originalVideoFilename);
                    $newVideoFilename = $safeVideoFilename.'-'.uniqid().'.'.$videoFile->guessExtension();

                    // Move the file to the directory where videos are stored
                    try {
                        $videoFile->move(
                            $this->getParameter('videos_directory'),
                            $newVideoFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    $video = new Video_post();
                    // updates the 'videoFilename' property to store the PDF file name
                    // instead of its contents
                    $video->setVideoFilename($newVideoFilename);
                    $video->setIdPost($post);
                    $entityManager->persist($video);
                    $entityManager->flush();
                }
            }
        }

            return $this->render('new.html.twig', array(
                'form' => $form->createView(),
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

    #[Route('/post/{id}', name: 'post_show')]
    public function show(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException(
                'No post found for id '.$id
            );
        }

        $pictures = $entityManager->getRepository(Picture_post::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );

        $videos = $entityManager->getRepository(Video_post::class)->findBy(
            ['id_post' => $id],
            ['id' => 'ASC']
        );


        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setDate(new \DateTimeImmutable());
            $comment->setIdPost($post);
            $entityManager->persist($comment);
            $entityManager->flush();
        }


        $page = $request->get('page', 1);
        $limit = $request->get('limit', 2);

        $qb = $entityManager->createQueryBuilder()
            ->from('App\Entity\Comment', 'c')
            ->select('c')
            ->setParameter('val', $id)
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
    

        return $this->render('detail.html.twig', [
            //'post_name' => $post->getName(),
            //'post_description' => $post->getDescription(),
            'post_group_figure' => $post->getGroupFigure(),
            'post' => $post,
            'pictures' => $pictures,
            'videos' => $videos,
            'comments' => $comments,
            'form' => $form->createView(),
            'page' => $page,
            'nbpage' => $nbpage,
            'gravatar' => $gravatar,
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/post/edit/{id}', name: 'post_edit')]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $post_db = $entityManager->getRepository(Post::class)->find($id);
        $pictures_db = $entityManager->getRepository(Picture_post::class)->findBy(
            ['id_post' => $id]
        );
        $videos_db = $entityManager->getRepository(Video_post::class)->findBy(
            ['id_post' => $id]
        );

        if (!$post_db) {
            throw $this->createNotFoundException(
                'No post found for id '.$id
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

            $entityManager->persist($post_db);
            $entityManager->flush();

            return $this->redirectToRoute('post_show', [
                'id' => $post_db->getId()
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
    public function updatePicture(int $id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $picture_db = $entityManager->getRepository(Picture_post::class)->find($id);

        if (!$picture_db) {
            throw $this->createNotFoundException(
                'No picture found for id '.$id
            );
        }

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

            $entityManager->persist($picture_db);
            $entityManager->flush();

            return $this->redirectToRoute('post_show', [
                'id' => $picture_db->getIdPost()
            ]);

        }

        return $this->render('new2.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/video/edit/{id}', name: 'video_edit')]
    public function updateVideo(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $video_db = $entityManager->getRepository(Video_post::class)->find($id);

        if (!$video_db) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }

        $video = new Video_post();        

        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            
            unlink($this->getParameter('videos_directory').'/'.$video_db->getPictureFilename());

            /** @var UploadedFile $pictureFile */
            //$videoFile = $form->getData();
            $videoFile = $form->get('video')->getData();

            // this condition is needed because the 'video' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($videoFile) {
                $originalVideoFilename = pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeVideoFilename = $slugger->slug($originalVideoFilename);
                $newVideoFilename = $safeVideoFilename.'-'.uniqid().'.'.$videoFile->guessExtension();

                // Move the file to the directory where pictures are stored
                try {
                    $videoFile->move(
                        $this->getParameter('videos_directory'),
                        $newVideoFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'videoFilename' property to store the PDF file name
                // instead of its contents
                $video->setVideoFilename($newVideoFilename);
            }

            $video_db->setVideoFilename($newVideoFilename);

            $entityManager->persist($video_db);
            $entityManager->flush();

            return $this->redirectToRoute('post_show', [
                'id' => $video_db->getIdPost()
            ]);

        }

        return $this->render('new.html.twig', array(
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
                unlink($this->getParameter('videos_directory').'/'.$video->getVideoFilename());
                $entityManager->remove($video);
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
    public function deletePicture(int $id, EntityManagerInterface $entityManager): Response
    {
        $picture = $entityManager->getRepository(Picture_post::class)->find($id);

        if (!$picture) {
            throw $this->createNotFoundException(
                'No picture found for id '.$id
            );
        }

        $id_post = $picture->getIdPost();

        unlink($this->getParameter('pictures_directory').'/'.$picture->getPictureFilename());

        $entityManager->remove($picture);
        $entityManager->flush();

        return $this->redirectToRoute('post_edit', [
            'id' => intval($id_post)
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    #[Route('/video/delete/{id}', name: 'video_delete')]
    public function deleteVideo(int $id, EntityManagerInterface $entityManager): Response
    {
        $video = $entityManager->getRepository(Video_post::class)->find($id);

        if (!$video) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }

        $id_post = $video->getIdPost();

        unlink($this->getParameter('videos_directory').'/'.$video->getVideoFilename());

        $entityManager->remove($video);
        $entityManager->flush();

        return $this->redirectToRoute('post_edit', [
            'id' => $id_post
        ]);
    }
}