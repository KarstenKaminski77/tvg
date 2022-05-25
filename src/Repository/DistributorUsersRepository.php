<?php

namespace App\Repository;

use App\Entity\DistributorUsers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DistributorUsers|null find($id, $lockMode = null, $lockVersion = null)
 * @method DistributorUsers|null findOneBy(array $criteria, array $orderBy = null)
 * @method DistributorUsers[]    findAll()
 * @method DistributorUsers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DistributorUsersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DistributorUsers::class);
    }

    /**
    * @return DistributorUsers[] Returns an array of DistributorUsers objects
    */
    public function findDistributorUsers($distributor_id)
    {
        $queryBuilder = $this->createQueryBuilder('du')
            ->andWhere('du.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->orderBy('du.id', 'DESC')
            ;

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }
}
