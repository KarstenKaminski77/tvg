<?php

namespace App\Controller;

use App\Entity\Notifications;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationsController extends AbstractController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('clinics/get-notification', name: 'get_notifications')]
    public function getNotifications(): Response
    {
        $response = '';

        if($this->getUser() != null) {

            $notifications = $this->em->getRepository(Notifications::class)->findBy([
                'clinic' => $this->getUser()->getClinic(),
                'isActive' => 1,
                'isRead' => 0
            ]);

            if(count($notifications) > 0){

                $i = 0;

                $response .= '
                <li>
                    <span class="notification-panel">';

                foreach($notifications as $notification){

                    $i++;
                    $mb = 'mb-3';

                    if($i == count($notifications)){

                        $mb = '';
                    }

                    $response .= '<div class="'. $mb .'">'. $notification->getNotification() .'</div>';
                }

                $response .= '
                    </span>
                </li>';

            } else {

                $response .= '<li><span class="notification-panel">You have no notifications</span></li>';
            }
        }

        return new JsonResponse($response);
    }

    #[Route('clinics/delete-notification', name: 'delete_notifications')]
    public function deleteNotifications(Request $request): Response
    {
        $notification = $this->em->getRepository(Notifications::class)->find($request->request->get('notification_id'));

        if($notification != null){

            $this->em->remove($notification);
            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> Notification successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $flash = '<b><i class="fa-solid fa-circle-xmark"></i> An error occurred, please try again later.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $response = [
            'flash' => $flash,
            'notifications' => $this->getNotifications(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/get-order-notifications', name: 'clinic_get_order_notification')]
    public function clinicGetOrderNotificationsAction(Request $request): Response
    {
        $clinic_id = $this->getUser()->getClinic()->getId();
        $notifications = $this->em->getRepository(Notifications::class)->findBy([
            'clinic' => $clinic_id,
            'isActive' => 1
        ]);
        $alerts = '';
        $i = 0;

        foreach($notifications as $notification){

            $alerts .= '
            <li>
                <span 
                    class="notification-panel"
                    data-order-id="'. $notification->getOrders()->getId() .'"
                    data-distributor-id=""
                >';

            foreach($notifications as $notification){

                $i++;
                $mb = 'mb-3';

                if($i == count($notifications)){

                    $mb = '';
                }

                $alerts .= '<div class="'. $mb .'">'. $notification->getNotification() .'</div>';
            }

            $alerts .= '
                </span>
            </li>';
        }

        $response = [
            'alert' => $alerts,
            'count' => $i
        ];

        return new JsonResponse($response);
    }
}
