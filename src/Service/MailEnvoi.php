<?php

namespace App\Service;

//use Symfony\Component\Mailer\MailerInterface;
//use Symfony\Component\Mime\Email;

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

class MailEnvoi
{

    public function sendEmail($to, $htmlText, MailerInterface $mailer)
       {
           /*$number = random_int(0, 100);

           $email = (new Email())
               ->from('mehal.samir@hotmail.fr')
               ->to('sam-77@hotmail.fr')
               //->cc('cc@example.com')
               //->bcc('bcc@example.com')
               //->replyTo('fabien@example.com')
               //->priority(Email::PRIORITY_HIGH)
               ->subject('Time for Symfony Mailer!')
               ->text('Envoie message avec ')
               ->html('<p>See Twig integration for better HTML integration! with</p><p>http://localhost:8000/validation/'.$number.'</p>'.$number);

           $mailer->send($email);*/

           $email = (new Email())
               ->from('mehal.samir@hotmail.fr')
               ->to(strval($to))
               //->cc('cc@example.com')
               //->bcc('bcc@example.com')
               //->replyTo('fabien@example.com')
               //->priority(Email::PRIORITY_HIGH)
               ->subject('Time for Symfony Mailer!')
               ->text('Envoie message avec ')
               ->html($htmlText);

           $mailer->send($email);

           /*if (!$mailer->send($email)) {
               echo 'Erreur de Mailer : ';// . $email->ErrorInfo;
           } else {
               echo 'Le message a été envoyé.';
           }*/

           /*return $this->render('post.html.twig', [
               'number' => $number,
           ]);*/

           //return $number;
       }
}