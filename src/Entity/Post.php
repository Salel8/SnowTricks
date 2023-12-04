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

use App\Entity\Comment;


#[ORM\Entity]
#[ORM\Table(name: 'post')]
class Post
{   
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id=null;

    #[ORM\Column(type: 'string', length: 140)]
    private string $name;
    
    #[ORM\Column(type: 'string', length: 1000)]
    private string $description;
    
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
