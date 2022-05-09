<?php

namespace App\Repository;

use App\Entity\OrderItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderItems|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItems|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItems[]    findAll()
 * @method OrderItems[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderItemsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItems::class);
    }

    /**
    * @return OrderItems[] Returns an array of OrderItems objects
    */

    public function findOrderDistributors($order_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement();

        return $this->createQueryBuilder('o')
            ->andWhere('o.orders = :order_id')
            ->setParameter('order_id', $order_id)
            ->orderBy('o.distributor', 'ASC')
            ->groupBy('o.distributor')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return OrderItems[] Returns an array of OrderItems objects
     */

    public function findByNotCancelled($order_id, $distributor_id)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orders = :order_id')
            ->setParameter('order_id', $order_id)
            ->andWhere('o.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->andWhere('o.isCancelled = :is_cancelled')
            ->setParameter('is_cancelled', 0)
            ->orderBy('o.distributor', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return OrderItems[] Returns an array of OrderItems objects
     */
    public function findSumTotalOrderItems($order_id, $distributor_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement();

        return $this->createQueryBuilder('o')
            ->select('SUM(o.total) as totals')
            ->andWhere('o.orders = :order_id')
            ->setParameter('order_id', $order_id)
            ->andWhere('o.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->groupBy('o.orders')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return OrderItems[] Returns an array of OrderItems objects
     */
    public function findByDistributorOrder($order_id, $distributor_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'oi', 'os')
            ->join('o.orders', 'oi')
            ->join('oi.distributor', 'os')
            ->andWhere('o.orders = :order_id')
            ->setParameter('order_id', $order_id)
            ->andWhere('o.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->andWhere()
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?OrderItems
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
