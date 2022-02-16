<?php

namespace App\Repository;

use App\Entity\Distributors;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Distributors|null find($id, $lockMode = null, $lockVersion = null)
 * @method Distributors|null findOneBy(array $criteria, array $orderBy = null)
 * @method Distributors[]    findAll()
 * @method Distributors[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DistributorsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Distributors::class);
    }

    // /**
    //  * @return Distributors[] Returns an array of Distributors objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Distributors
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
