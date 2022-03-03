<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

    /**
    * @return Products[] Returns an array of Products objects
    */
    public function findBySearch($keyword)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :keyword')
            ->setParameter('keyword', $keyword .'%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByKeyString($keywords): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p','dp')
            ->Join('p.distributorProducts', 'dp')
            ->where("MATCH_AGAINST(p.name,p.activeIngredient,p.description) AGAINST(:search_term boolean) > 0")
            ->setParameter('search_term', $keywords.'*')
            ->getQuery()
            ->getArrayResult();
        return $queryBuilder;
    }

    /*
    public function findOneBySomeField($value): ?Products
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
