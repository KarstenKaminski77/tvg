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

    /**
    * @return ClinicCommunicationMethods[] Returns an array of ClinicCommunicationMethods objects
    */

    public function findByClinic($clinic_id)
    {
        $queryBuilder =  $this->createQueryBuilder('c')
            ->andWhere('c.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->andWhere('c.communicationMethod > 1')
            ->andWhere('c.isActive = 1')
            ->orderBy('c.id', 'DESC')
        ;

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

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
