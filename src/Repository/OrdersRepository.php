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

    public function findByDistributor($distributor_id, $clinic_id, $status_id, $dates)
    {
        $queryBuilder =  $this->createQueryBuilder('o')
            ->select('o', 'oi', 'os')
            ->join('o.orderItems', 'oi')
            ->join('o.orderStatuses', 'os')
            ->join('os.status', 's')
            ->andWhere('oi.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id)
            ->andWhere('os.distributor = :distributor_id')
            ->setParameter('distributor_id', $distributor_id);

        if(!empty($clinic_id)){

            $queryBuilder
                ->andWhere('o.clinic = :clinic_id')
                ->setParameter('clinic_id', $clinic_id);
        }

        if($dates != null){

            $date = explode(' - ', $dates);

            $queryBuilder
                ->andWhere('DATE(o.created) >= :start')
                ->setParameter('start', $date[0])
                ->andWhere('DATE(o.created) <= :end')
                ->setParameter('end', $date[1]);
        }

        if(!empty($status_id)){

            $queryBuilder
                ->andWhere('s.id = :status')
                ->setParameter('status', $status_id);
        }

        $queryBuilder
            ->orderBy('o.id', 'DESC')
        ;

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

    /**
     * @return Orders[] Returns an array of Orders objects
     */

    public function findClinicOrders($clinic_id,$distributor_id, $date, $status)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        $stmt = $conn->prepare($sql)->executeQuery();

        $queryBuilder = $this->createQueryBuilder('o')
            ->select('o', 'oi','os')
            ->join('o.orderItems', 'oi')
            ->join('o.orderStatuses', 'os')
            ->join('os.status', 's')
            ->andWhere('o.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id);

        if(!empty($distributor_id)){

            $queryBuilder
                ->andWhere('oi.distributor = :distributor_id')
                ->setParameter('distributor_id', $distributor_id);
        }

        if(!empty($date)){

            $dates = explode(' - ', $date);

            $queryBuilder
                ->andWhere('DATE(o.created) >= :start')
                ->setParameter('start', $dates[0])
                ->andWhere('DATE(o.created) <= :end')
                ->setParameter('end', $dates[1]);
        }

        if(!empty($status)){

            $queryBuilder
                ->andWhere('s.id = :status')
                ->setParameter('status', $status);
        }

        $queryBuilder
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
