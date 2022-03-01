<?php

namespace App\Repository;

use App\Entity\Clinics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Clinics|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clinics|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clinics[]    findAll()
 * @method Clinics[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClinicsRepository extends ServiceEntityRepository
{
    private $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clinics::class);
        $this->conn = $this->_em->getConnection();
    }

    public function getClinicAddresses($clinic_id): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c,ca')
            ->Join('c.addresses', 'ca')
            ->andWhere('c.id = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->andWhere('ca.isActive = 1');
        return $queryBuilder->getQuery()->getResult();
    }

    public function getClinicCommunicationMethods($clinic_id): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c,cm')
            ->Join('c.clinicCommunicationMethods', 'cm')
            ->andWhere('c.id = :clinic_id')
            ->setParameter('clinic_id', $clinic_id)
            ->andWhere('cm.isActive = 1');
        return $queryBuilder->getQuery()->getResult();
    }

    public function getClinicDefaultAddresses($clinic_id, $address_id)
    {
        $sql = "
            UPDATE 
                 addresses
            SET
                is_default = 0
            WHERE 
                  clinic_id = $clinic_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $sql = "
            UPDATE 
                 addresses
            SET
                is_default = 1
            WHERE 
                  id = $address_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    }

    public function getClinicUsers($clinic_id): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c,cu')
            ->Join('c.clinicUsers', 'cu')
            ->where('c.id = :clinic_id')
            ->setParameter('clinic_id', $clinic_id);
        return $queryBuilder->getQuery()->getResult();
    }

    // /**
    //  * @return Clinics[] Returns an array of Clinics objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

}
