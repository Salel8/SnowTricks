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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class CommentController extends AbstractController
{
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
    }

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