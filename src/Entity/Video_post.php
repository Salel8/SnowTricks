<?php
// src/Entity/Video_post.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Post;


#[ORM\Entity]
#[ORM\Table(name: 'video_post')]
class Video_post
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id=null;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[JoinColumn(name: 'id_post_id', referencedColumnName: 'id')]
    private object $id_post;
    
    #[ORM\Column(type: 'string', length: 550)]
    private string $videoFilename;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getIdPost(): int
    {
        return $this->id_post;
    }

    public function setIdPost(object $id_post): self
    {
        $this->id_post = $id_post;

        return $this;
    }

    public function getVideoFilename(): string
    {
        return $this->videoFilename;
    }

    public function setVideoFilename(string $videoFilename): self
    {
        $this->videoFilename = $videoFilename;

        return $this;
    }
}