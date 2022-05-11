<?php

namespace App\Services;

use App\Entity\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OrderStatusManager
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function updateOrderStatus() {

        $conn = $this->em->getConnection();

        $sql = "
        SELECT
            id,
            modified
        FROM
            order_status
        WHERE
            status_id = 5;
        ";

        $stmt = $conn->executeQuery($sql);
        $result = $stmt->fetchAllAssociative();

        foreach($result as $res){

            $date_time = $res['modified'];
            $date = date('Y-m-d', strtotime($date_time));
            $id = $res['id'];

            if($date == date('Y-m-d')){

                $hour = date('H', strtotime($date_time . '+1 hour'));
                $hour_now = date('H');

                if($hour == $hour_now){

                    $sql = "
                    UPDATE
                        order_status
                    SET
                        status_id = 6
                    WHERE
                        id = $id
                    ";

                    $stmt = $conn->executeQuery($sql);
                }
            }
        }
    }
}