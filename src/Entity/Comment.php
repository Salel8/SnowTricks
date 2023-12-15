<?php
// src/Entity/Comment.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
//use App\Repository\CommentRepository;
//use Doctrine\ORM\Mapping\Entity;
//use Doctrine\ORM\Mapping\Table;
//use Doctrine\ORM\Mapping\Column;
//use Doctrine\DBAL\Types\Types;

use App\Entity\Post;

#[ORM\Entity]
#[ORM\Table(name: 'comment')]
class Comment
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id=null;

    #[Assert\NotBlank(message: "L'auteur est obligatoire")]
    #[Assert\Length(min: 3, max: 140, minMessage: "L'auteur doit faire au moins {{ limit }} caractères", maxMessage: "L'auteur ne peut pas faire plus de {{ limit }} caractères")]
    #[ORM\Column(type: 'string', length: 140)]
    private string $author;
    
    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    #[ORM\Column(type: 'datetime', name: 'date')]
    private ?\DateTimeInterface $date;
    
    #[Assert\NotBlank(message: "Le contenu est obligatoire")]
    #[Assert\Length(min: 3, max: 5000, minMessage: "Le contenu doit faire au moins {{ limit }} caractères", maxMessage: "Le contenu ne peut pas faire plus de {{ limit }} caractères")]
    #[ORM\Column(type: 'string', length: 5000)]
    private string $content;

    #[Assert\NotBlank(message: "Le travail est obligatoire")]
    #[Assert\Length(min: 3, max: 500, minMessage: "L'email doit faire au moins {{ limit }} caractères", maxMessage: "L'email ne peut pas faire plus de {{ limit }} caractères")]
    #[ORM\Column(type: 'string', length: 500)]
    private string $email;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'id_post_id', referencedColumnName: 'id')]
    private object $id_post;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setIdPost(object $id_post)
    {
        $this->id_post = $id_post;
    }

    public function getIdPost(): object
    {
        return $this->id_post;
    }
}