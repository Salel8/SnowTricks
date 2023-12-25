<?php
// src/Entity/Post.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
//use App\Repository\PostRepository;
//use Doctrine\ORM\Mapping\Entity;
//use Doctrine\ORM\Mapping\Table;
//use Doctrine\ORM\Mapping\Column;
//use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use App\Entity\Comment;


#[ORM\Entity]
#[ORM\Table(name: 'post')]
#[UniqueEntity('name')]
class Post
{   
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id=null;

    #[Assert\UniqueEntity(message: "Le titre de l'article dot être unique")]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, max: 140, minMessage: "Le titre doit faire au moins {{ limit }} caractères", maxMessage: "Le titre ne peut pas faire plus de {{ limit }} caractères")]
    #[ORM\Column(type: 'string', length: 140, unique: true)]
    private string $name;
    
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(min: 3, max: 1000, minMessage: "La description doit faire au moins {{ limit }} caractères", maxMessage: "La description ne peut pas faire plus de {{ limit }} caractères")]
    #[ORM\Column(type: 'string', length: 1000)]
    private string $description;
    
    #[Assert\NotBlank(message: "Le groupe est obligatoire")]
    #[Assert\Length(min: 3, max: 550, minMessage: "Le groupe doit faire au moins {{ limit }} caractères", maxMessage: "Le groupe ne peut pas faire plus de {{ limit }} caractères")]
    #[ORM\Column(type: 'string', length: 550)]
    private string $group_figure;

    /**
     * One post has many comments. This is the inverse side.
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(mappedBy: 'id_post', targetEntity: Comment::class)]
    //#[ORM\JoinColumn(name: 'comments', referencedColumnName: 'id')]
    private Collection $comments;
    

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setGroupFigure(string $group_figure): void
    {
        $this->group_figure = $group_figure;
    }

    public function getGroupFigure()
    {
        return $this->group_figure;
    }

    /*public function setComments($comments): void
    {
        $this->comments = $comments;
    }*/

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
