<?php

namespace App\Repository;

use App\Entity\CommunicationMethods;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CommunicationMethods|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommunicationMethods|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommunicationMethods[]    findAll()
 * @method CommunicationMethods[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunicationMethodsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunicationMethods::class);
    }
    /**
    * @return CommunicationMethods[] Returns an array of CommunicationMethods objects
    */
    public function findByNotInApp()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id > 1')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?CommunicationMethods
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
