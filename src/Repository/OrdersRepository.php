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
        return $this->createQueryBuilder('o')
            ->select('o', 'oi', 'os')
            ->join('o.orderItems', 'oi')
            ->join('o.orderStatuses', 'os')
            ->andWhere('oi.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->andWhere('os.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Orders[] Returns an array of Orders objects
     */

    public function findClinicOrders($clinic_id)
    {
        $sql = " 
            SELECT 
                o.id, 
                oi.distributor_id, 
                d.distributor_name,
                o.created,
                c.id as clinic_id,
                (
                    SELECT
                        status
                    FROM
                        order_status
                    WHERE
                        orders_id = o.id
                    AND 
                        distributor_id = oi.distributor_id
                ) as status,
                (
                    SELECT
                        SUM(unit_price)
                    FROM
                        order_items
                    WHERE
                        orders_id = o.id
                    AND
                        distributor_id = d.id
                ) as total
            FROM
                orders o
                INNER JOIN order_items oi ON o.id = oi.orders_id
                INNER JOIN distributors d ON d.id = oi.distributor_id
                INNER JOIN clinics c ON o.clinic_id = c.id
            WHERE
                o.clinic_id = :clinic_id 
            GROUP BY oi.orders_id, oi.distributor_id
            ORDER BY o.id DESC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['clinic_id' => $clinic_id]);
        return $result->fetchAllAssociative();
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
