<?php

namespace App\Repository;

use App\Entity\DistributorUserPermissions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DistributorUserPermissions>
 *
 * @method DistributorUserPermissions|null find($id, $lockMode = null, $lockVersion = null)
 * @method DistributorUserPermissions|null findOneBy(array $criteria, array $orderBy = null)
 * @method DistributorUserPermissions[]    findAll()
 * @method DistributorUserPermissions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DistributorUserPermissionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DistributorUserPermissions::class);
    }

    public function add(DistributorUserPermissions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DistributorUserPermissions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return DistributorUserPermissions[] Returns an array of DistributorUserPermissions objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DistributorUserPermissions
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
