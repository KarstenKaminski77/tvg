<?php

namespace App\Repository;

use App\Entity\ClinicUsers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClinicUsers|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClinicUsers|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClinicUsers[]    findAll()
 * @method ClinicUsers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClinicUsersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClinicUsers::class);
    }

    /**
    * @return ClinicUsers[] Returns an array of ClinicUsers objects
    */
    public function findClinicUsers($clinic_id)
    {
        $queryBuilder = $this->createQueryBuilder('cu')
            ->andWhere('cu.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->orderBy('cu.id', 'DESC')
        ;

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

    /*
    public function findOneBySomeField($value): ?ClinicUsers
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
