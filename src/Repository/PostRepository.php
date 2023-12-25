<?php

namespace App\Repository;

use App\Entity\Post;
//use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Doctrine\Persistence\ManagerRegistry;

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

    public function findAllWithPagination($value, $page, $limit) {
        $qb = $this->createQueryBuilder('c')
            ->join('c.comments', 'u')
            ->andWhere('u.id = :val')
            ->setParameter('val', $value)
            //->orderBy('c.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }

    /*public function findAllWithPagination($page, $limit) {
        $qb = $this->createQueryBuilder('b')
            ->join('b.comments', 'u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }*/

    public function findAllCommentsWithPagination(int $value, $page, $limit): array
    {
        // automatically knows to select Products
        // the "p" is an alias you'll use in the rest of the query
        $qb = $this->createQueryBuilder('p')
            ->join('p.comments', 'u')
            ->where('u.id = :val')
            ->setParameter('val', $value)
            //->orderBy('p.price', 'ASC');
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);


        $query = $qb->getQuery();

        return $query->execute();

        // to get just one result:
        // $product = $query->setMaxResults(1)->getOneOrNullResult();
    }
}

