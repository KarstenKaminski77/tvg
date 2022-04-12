<?php

namespace App\Repository;

use App\Entity\Lists;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Lists|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lists|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lists[]    findAll()
 * @method Lists[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lists::class);
    }

    public function getClinicLists($clinic_id): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('l,li')
            ->leftJoin('l.listItems', 'li')
            ->andWhere('l.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->andWhere('l.listType != :list_type')
            ->setParameter('list_type', 'favourite');
        return $queryBuilder->getQuery()->getResult();
    }

    public function getLastList($clinic_id): object
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('l')
            ->andWhere('l.clinic = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->orderBy('l.id', 'DESC')
            ->setMaxResults(1);
        return $queryBuilder->getQuery()->getResult();
    }
}
