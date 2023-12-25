<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findLastPosts()
    {
        return $this->findBy([], ['publicationDate' => 'DESC']);
    }
}

