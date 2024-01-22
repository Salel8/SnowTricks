<?php
// src/Controller/Comment.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\CommentType;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Entity\Post;
/*use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;*/
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class CommentController extends AbstractController
{
    /*public function newComment(object $post, Request $request, EntityManagerInterface $entityManager)
    {
        $comment = new Comment();
        $comment->setDate(new \DateTimeImmutable());
        $comment->setIdPost($post);

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            
            //$em->persist($comment);
            //$em->flush();

            $comment = $form->getData();
            $entityManager->persist($comment);
            $entityManager->flush();
        }

        return $form;
    }*/

    //#[Route('/posts', name: 'all_posts')]
    /*public function getLastComments($id_post, EntityManagerInterface $entityManager): array
    {
        $repository = $entityManager->getRepository(Comment::class);
        // look for *all* Product objects
        //$comments = $repository->findAll();

        // look for multiple Product objects matching the name, ordered by price
        $comments = $repository->findBy(
            ['id_post' => $id_post],
            ['date' => 'ASC']
        );

        return $comments;
    }*/

    /*public function getLastCommentsWithPaginationn($id_post, EntityManagerInterface $entityManager, CommentRepository $commentRepository): array
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $comments = $commentRepository->findAllWithPagination($page, $limit);

        return $comments;
    }*/

    public function get_gravatar_url( $email ) {
        // Trim leading and trailing whitespace from
        // an email address and force all characters
        // to lower case
        $address = strtolower( trim( $email ) );
      
        // Create an SHA256 hash of the final string
        $hash = hash( 'sha256', $address );
      
        // Grab the actual image URL
        return 'https://www.gravatar.com/avatar/' . $hash;
    }

    public function newComment(object $post, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
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

        return $form;
        /*return $this->render('detail.html.twig', array(
            'form' => $form->createView()
        ));*/
        /*$response = $this->forward('App\Controller\PostController::post_show', [
            'formComment' => $form->createView(),
        ]);

        return $response;*/
    }

    /*public function getLastCommentsWithPagination($post, $page, $limit, EntityManagerInterface $entityManager): array
    {
        //$page = $request->get('page', 1);
        //$limit = $request->get('limit', 2);

        $qb = $entityManager->createQueryBuilder()
            ->from('App\Entity\Comment', 'c')
            ->select('c')
            ->setParameter('val', $post->getId())
            ->andwhere('c.id_post = :val');
            //->setFirstResult(($page - 1) * $limit)
            //->setMaxResults($limit);

        $comments = $qb->getQuery()->getResult();

        return $comments;
    }*/

    public function deleteComment($id, EntityManagerInterface $entityManager) {
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
    }

}