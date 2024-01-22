<?php
//src/Service/FileUploader.php
namespace App\Service;

/*use Symfony\Component\HttpFoundation\File\Exception\FileException;
//use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Picture_post;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;*/

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Video_post;
use App\Form\VideoType;
use App\Entity\Picture_post;
use App\Form\PictureType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class FileUploader
{
    public function __construct(
        private string $targetDirectory,
        //private SluggerInterface $slugger,
    ) {
    }

    public function uploadPicture($post, $pictureFile, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator)
    {
        $originalPictureFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safePictureFilename = $slugger->slug($originalPictureFilename);
        $newPictureFilename = $safePictureFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

        // Move the file to the directory where pictures are stored
        try {
            /*$pictureFile->move(
                $this->getParameter('pictures_directory'),
                $newPictureFilename
            );*/
            $pictureFile->move($this->getTargetDirectory(), $newPictureFilename);
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

        //return $fileName;
    }

    public function editPicture($picture_db, $pictureFile, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator)
    {
        $originalPictureFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safePictureFilename = $slugger->slug($originalPictureFilename);
        $newPictureFilename = $safePictureFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

        // Move the file to the directory where pictures are stored
        try {
            /*$pictureFile->move(
                $this->getParameter('pictures_directory'),
                $newPictureFilename
            );*/
            $pictureFile->move($this->getTargetDirectory(), $newPictureFilename);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        //$picture = new Picture_post();
        // updates the 'pictureFilename' property to store the PDF file name
        // instead of its contents
        //$picture->setPictureFilename($newPictureFilename);
        //$picture->setIdPost($post);
        $picture_db->setPictureFilename($newPictureFilename);

        $errors = $validator->validate($picture_db);
        if (count($errors) > 0) {
            return new Response((string) $errors, 400);
        }

        $entityManager->persist($picture_db);
        $entityManager->flush();

        //return $fileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function uploadVideo($videoFiles, $post, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
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

    public function editVideo($video_db, $videoFile, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        //$video = new Video_post();
        $video_db->setVideoFilename($videoFile);
        //$video->setIdPost($post);

        $errors = $validator->validate($video_db);
        if (count($errors) > 0) {
            return new Response((string) $errors, 400);
        }
        $entityManager->persist($video_db);
        $entityManager->flush();
    }

    public function deleteFile($file, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($file);
        $entityManager->flush();
    }
}