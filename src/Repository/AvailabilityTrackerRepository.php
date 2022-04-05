<?php

namespace App\Repository;

use App\Entity\AvailabilityTracker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AvailabilityTracker|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvailabilityTracker|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvailabilityTracker[]    findAll()
 * @method AvailabilityTracker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvailabilityTrackerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityTracker::class);
    }

     /**
      * @return AvailabilityTracker[] Returns an array of AvailabilityTracker objects
      */
    public function getSavedTrackers($product_id,$clinic_id)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.product = :product_id')
            ->setParameter('product_id', $product_id)
            ->andWhere('a.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
