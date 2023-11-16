<?php
// src/Controller/User.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\UserType;
use App\Entity\User;
//use App\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\ORM\EntityManagerInterface;
//use App\Entity\Video_post;
//use App\Form\VideoType;
//use App\Entity\Picture_post;
//use App\Form\PictureType;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
//use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserController extends AbstractController
{
    #[Route('/register', name: 'page_add_user')]
    public function register(Request $request, EntityManagerInterface $entityManager,UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer/*, SluggerInterface $slugger*/): Response
    {
        $user = new User();
        //$user->setUsername('Sam');
        //$user->setEmail('adresse mail');
        //$user->setPassword('Mot de passe');

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();

            $plaintextPassword = $user->getPassword();
            
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );
            $user->setPassword($hashedPassword);

            $token_inscription = uniqid(rand(), true);
            $user->setToken($token_inscription);

            $entityManager->persist($user);
            $entityManager->flush();

            //on envoie un mail de confirmation
            $email = (new Email())
                ->from('mehal.samir@hotmail.fr')
                ->to($user->getEmail())
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject('Valider votre inscription !')
                ->text('Sending emails is fun again!')
                ->html('<p>See Twig integration for better HTML integration!</p><p>http://localhost:8000/validation/'.$token_inscription.'</p>');

            $mailer->send($email);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('login/registration.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    #[Route('/validation/{token}', name: 'app_validate')]
    public function validation(string $token, Request $request, EntityManagerInterface $entityManager, AuthenticationUtils $authenticationUtils): Response
    {
        $user_db = $entityManager->getRepository(User::class)->findOneBy(['token' => $token]);

        $roles[] = 'ROLE_USER';
        $user_db->setRoles($roles);
        //$user_db->setToken(null);

        $entityManager->persist($user_db);
        $entityManager->flush();

        
        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login')]
    public function getAllUsers(Request $request, EntityManagerInterface $entityManager, AuthenticationUtils $authenticationUtils): Response
    {
        $repository = $entityManager->getRepository(User::class);
        // look for *all* Product objects
        $users = $repository->findAll();

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            
            //$name = $request->query->get('name');
            //$password = $request->query->get('password');

            $user = $form->getData();

            foreach ($users as $user_db){
                if ($user->getName()==$user_db->getName() && $user->getPassword()==$user_db->getPassword() && $user_db->getValidityAccount()=='validate'){
                    //enregistrer quelque chose dans la session pour garder la connexion
                }
            }
            return $this->redirectToRoute('all_posts');
        }
        

        /*return $this->render('user.html.twig', array(
            'form' => $form->createView(),
        ));*/
        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/password/forget', name: 'forget_password')]
    public function forgetPassword(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            //$name = $request->query->get('name');

            $user = $form->getData();
            //$username = $form->get('username')->getData();

            $repository = $entityManager->getRepository(User::class);
            $user_db = $repository->findOneBy(['username' => $user->getUsername()]);

            $token_forget = uniqid(rand(), true);
            $user_db->setToken($token_forget);

            $entityManager->persist($user_db);
            $entityManager->flush();

            //on envoie un mail de réinitialisation
            $email = (new Email())
                ->from('mehal.samir@hotmail.fr')
                ->to($user_db->getEmail())
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject('Time for Symfony Mailer!')
                ->text('Sending emails is fun again!')
                ->html('<p>See Twig integration for better HTML integration!</p><p>http://localhost:8000/password/reinitialisation/'.$token_forget.'</p>');

            $mailer->send($email);
        }

        

        return $this->render('login/forgot.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    #[Route('/password/reinitialisation/{token}', name: 'reinitialisation_password')]
    public function reinitialisationPassword(string $token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user_db = $entityManager->getRepository(User::class)->findOneBy(['token' => $token]);
        //$repository = $entityManager->getRepository(User::class);
        //$user_db = $repository->findOneBy(['username' => 'Keyboard']);

            if (!$user_db) {
                throw $this->createNotFoundException(
                    'No user found for id '.$token
                );
            }   
        
        $user = new User();
            
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();

            $plaintextPassword = $user->getPassword();
            
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );

            $user_db->setPassword($hashedPassword);
            //$user_db->setToken('null');
            $entityManager->persist($user_db);
            $entityManager->flush();

            return $this->redirectToRoute('all_posts');
        }

        return $this->render('login/reset.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}