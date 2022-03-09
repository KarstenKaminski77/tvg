<?php

namespace App\Repository;

use App\Entity\ProductNotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductNotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductNotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductNotes[]    findAll()
 * @method ProductNotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductNotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductNotes::class);
    }

    /**
    * @return ProductNotes[] Returns an array of ProductNotes objects
    */

    public function findNotes($product_id, $clinic_id) :array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.product = :product_id')
            ->setParameter('product_id', $product_id)
            ->andWhere('p.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ProductNotes
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
