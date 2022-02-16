<?php

namespace App\Repository;

use App\Entity\ProductNotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductNotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductNotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductNotes[]    findAll()
 * @method ProductNotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductNotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductNotes::class);
    }

    // /**
    //  * @return ProductNotes[] Returns an array of ProductNotes objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductNotes
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
