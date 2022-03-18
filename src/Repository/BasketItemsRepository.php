<?php

namespace App\Repository;

use App\Entity\BasketItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BasketItems|null find($id, $lockMode = null, $lockVersion = null)
 * @method BasketItems|null findOneBy(array $criteria, array $orderBy = null)
 * @method BasketItems[]    findAll()
 * @method BasketItems[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BasketItemsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BasketItems::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(BasketItems $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(BasketItems $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    public function getTotalItems($basket_id)
    {
        return $this->createQueryBuilder('b')
            ->select('SUM(b.qty) AS item_count, SUM(b.total) AS total')
            ->andWhere('b.basket = :basket_id')
            ->setParameter('basket_id', $basket_id)
            ->groupBy('b.basket')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?BasketItems
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
