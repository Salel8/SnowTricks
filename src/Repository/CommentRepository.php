<?php

namespace App\Repository;

use App\Entity\Comment;
//use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findLastPosts()
    {
        return $this->findBy([], ['publicationDate' => 'DESC']);
    }

    public function findByWithPagination($value, $page, $limit) {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.name = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }

    public function findAllWithPagination($page, $limit) {
        $qb = $this->createQueryBuilder('b')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }

    public function findAllForPagination($post) {
        $qb = $this->createQueryBuilder('b')
            ->from('App\Entity\Comment', 'c')
            ->select('c')
            ->setParameter('val', $post->getId())
            ->andwhere('c.id_post = :val');
        return $qb->getQuery()->getResult();
    }
}

