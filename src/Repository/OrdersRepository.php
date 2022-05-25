<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Orders|null find($id, $lockMode = null, $lockVersion = null)
 * @method Orders|null findOneBy(array $criteria, array $orderBy = null)
 * @method Orders[]    findAll()
 * @method Orders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    /**
    * @return Orders[] Returns an array of Orders objects
    */

    public function findByDistributor($distributor_id)
    {
        $queryBuilder =  $this->createQueryBuilder('o')
            ->select('o', 'oi', 'os')
            ->join('o.orderItems', 'oi')
            ->join('o.orderStatuses', 'os')
            ->andWhere('oi.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->andWhere('os.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->orderBy('o.id', 'DESC')
        ;

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

    /**
     * @return Orders[] Returns an array of Orders objects
     */

    public function findClinicOrders($clinic_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";

        $stmt = $conn->prepare($sql)->executeQuery();

        $queryBuilder = $this->createQueryBuilder('o')
            ->select('o', 'oi','os')
            ->join('o.orderItems', 'oi')
            ->join('o.orderStatuses', 'os')
            ->andWhere('o.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->addGroupBy('oi.orders')
            ->addGroupBy('oi.distributor')
            ->orderBy('o.id', 'DESC')
            ;

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

    /*
    public function findOneBySomeField($value): ?Orders
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
