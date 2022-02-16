<?php

namespace App\Repository;

use App\Entity\Clinics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Clinics|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clinics|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clinics[]    findAll()
 * @method Clinics[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClinicsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clinics::class);
    }

    // /**
    //  * @return Clinics[] Returns an array of Clinics objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Clinics
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
