<?php

namespace App\Repository;

use App\Entity\ClinicUserPermissions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClinicUserPermissions>
 *
 * @method ClinicUserPermissions|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClinicUserPermissions|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClinicUserPermissions[]    findAll()
 * @method ClinicUserPermissions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClinicUserPermissionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClinicUserPermissions::class);
    }

    public function add(ClinicUserPermissions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ClinicUserPermissions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ClinicUserPermissions[] Returns an array of ClinicUserPermissions objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ClinicUserPermissions
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
