<?php

namespace App\Repository;

use App\Entity\ClinicCommunicationMethods;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClinicCommunicationMethods|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClinicCommunicationMethods|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClinicCommunicationMethods[]    findAll()
 * @method ClinicCommunicationMethods[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClinicCommunicationMethodsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClinicCommunicationMethods::class);
    }

    // /**
    //  * @return ClinicCommunicationMethods[] Returns an array of ClinicCommunicationMethods objects
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
    public function findOneBySomeField($value): ?ClinicCommunicationMethods
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
