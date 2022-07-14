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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function findByKeyString($keywords, $categories, $filters, $manufacturers, $distributors, $clinic_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement();

        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p','dp','d','c','pm')
            ->join('p.distributorProducts', 'dp')
            ->join('dp.distributor', 'd')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.productManufacturers', 'pm')
            ->leftJoin('p.productFavourites', 'pf')
            ->andWhere("MATCH_AGAINST(p.name,p.activeIngredient,p.description) AGAINST(:search_term boolean) > 0")
            ->setParameter('search_term', '*'.$keywords.'*')
            ->andWhere('p.isPublished = 1');

        if($categories != null && count($categories) > 0){

            $queryBuilder->andWhere("c.id IN (:categories)")
                ->setParameter('categories', $categories);
        }

        if(count($filters) > 0){

            if(in_array('favourite', $filters)){

                $queryBuilder->andWhere("pf.clinic = :clinic")
                    ->setParameter('clinic', $clinic_id);
            }

            if(in_array('in-stock', $filters)){

                $queryBuilder->andWhere("dp.stockCount > :in_stock")
                    ->setParameter('in_stock', 0);
            }
        }

        if(count($manufacturers) > 0){

            $queryBuilder->andWhere("pm.manufacturers IN (:manufacturers)")
                ->setParameter('manufacturers', $manufacturers);
        }

        if(count($distributors) > 0){

            $queryBuilder->andWhere("dp.distributor IN (:distributors)")
                ->setParameter('distributors', $distributors);
        }

        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

    public function findByListId($product_ids)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p','dp','d','c','pm')
            ->Join('p.distributorProducts', 'dp')
            ->join('dp.distributor', 'd')
            ->join('p.category', 'c')
            ->join('p.productManufacturers', 'pm')
            ->leftJoin('p.productFavourites', 'pf')
            ->andWhere("dp.product IN (:product_ids)")
            ->setParameter('product_ids', $product_ids)
            ->andWhere('p.isPublished = 1');
        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }

    public function adminFindAll()
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.name', 'ASC');
        return [$queryBuilder->getQuery(), $queryBuilder->getQuery()->getResult()];
    }
}
