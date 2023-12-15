<?php
// src/Entity/Picture_post.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Post;


#[ORM\Entity]
#[ORM\Table(name: 'picture_post')]
class Picture_post
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id=null;

    #[ORM\ManyToOne(targetEntity: Post::class, cascade: ["persist"])]
    #[JoinColumn(name: 'id_post_id', referencedColumnName: 'id')]
    private object $id_post;
    
    #[ORM\Column(type: 'string', length: 550)]
    private string $pictureFilename;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getIdPost(): object
    {
        return $this->id_post;
    }

    public function setIdPost(object $id_post): self
    {
        $this->id_post = $id_post;

        return $this;
    }

    public function getPictureFilename(): string
    {
        return $this->pictureFilename;
    }

    public function setPictureFilename(string $pictureFilename): self
    {
        $this->pictureFilename = $pictureFilename;

        return $this;
    }
}