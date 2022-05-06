<?php

namespace App\Repository;

use App\Entity\Baskets;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Baskets|null find($id, $lockMode = null, $lockVersion = null)
 * @method Baskets|null findOneBy(array $criteria, array $orderBy = null)
 * @method Baskets[]    findAll()
 * @method Baskets[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BasketsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Baskets::class);
    }

    public function getClinicTotalItems($clinic_id)
    {
        return $this->createQueryBuilder('b')
            ->select('SUM(bi.qty) AS item_count, SUM(bi.total) AS total')
            ->join('b.basketItems', 'bi')
            ->andWhere('b.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->andWhere('b.status = :basket_status')
            ->setParameter('basket_status', 'active')
            ->groupBy('b.clinic')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()
            ;
    }

    public function getBasketTotalItems($basket_id)
    {
        return $this->createQueryBuilder('b')
            ->select('SUM(bi.qty) AS item_count, SUM(bi.total) AS total')
            ->join('b.basketItems', 'bi')
            ->andWhere('b.id = :basket_id')
            ->setParameter('basket_id', $basket_id)
            ->groupBy('b.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()
            ;
    }

    public function findActiveBaskets($clinic_id)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', 'active')
            ->andWhere('b.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->orderBy('b.isDefault', 'DESC')
            ->addOrderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
