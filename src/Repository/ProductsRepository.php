<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findByKeystring($keywords): object
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->addSelect("MATCH_AGAINST (p.*, :searchterm 'IN NATURAL MODE') as score")
            ->add('where', 'MATCH_AGAINST(p.name, p.activeIngredient, p.description, :search_term) > 0.8')
            ->setParameter('search_term', $keywords)
            ->orderBy('score', 'desc')
            ->getQuery()
            ->getResult();
        return $queryBuilder->getQuery();
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
