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

    public function getDistributorProduct($distributor_id, $product_id): array
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('d,dp')
            ->Join('d.distributorProducts', 'dp')
            ->andWhere('dp.product = :product_id')
            ->setParameter('product_id', $product_id)
            ->andWhere('dp.distributor = :distributor_id ')
            ->setParameter('distributor_id', $distributor_id);
        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getDistributorUsers($distributor_id): array
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('d,du')
            ->Join('d.distributorUsers', 'du')
            ->where('d.id = :distributor_id')
            ->setParameter('distributor_id', $distributor_id);
        return $queryBuilder->getQuery()->getResult();
    }
}
