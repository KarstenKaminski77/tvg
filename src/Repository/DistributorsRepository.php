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
            ->select('d,dp,p')
            ->Join('d.distributorProducts', 'dp')
            ->Join('dp.product', 'p')
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

    /**
     * @return Distributors[] Returns an array of OrderItems objects
     */
    public function findByOrderId($order_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement();

        return $this->createQueryBuilder('d')
            ->select('d', 'oi')
            ->join('d.orderItems', 'oi')
            ->andWhere('oi.orders = :val')
            ->setParameter('val', $order_id)
            ->groupBy('d.id')
            ->getQuery()
            ->getResult()
            ;
    }
}
