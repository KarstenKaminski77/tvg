<?php

namespace App\Controller;

use App\Entity\ChatMessages;
use App\Entity\ChatParticipants;
use App\Entity\Clinics;
use App\Entity\Distributors;
use App\Entity\Notifications;
use App\Entity\Orders;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatMessagesController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/distributors/send-message', name: 'distributor_send_message')]
    #[Route('/clinics/send-message', name: 'clinic_send_message')]
    public function sendMessageAction(Request $request): Response
    {
        $data = $request->request;
        $message = (string) $data->get('message');
        $order_id = (int) $data->get('order_id');
        $is_clinic = $data->get('clinic');
        $is_distributor = $data->get('distributor');
        $distributor_id = $data->get('distributor_id');
        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $distributor_repo = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $clinic_repo = $this->em->getRepository(Clinics::class)->find($order->getClinic()->getId());
        $date_sent = '';

        $chat_message = new ChatMessages();

        $chat_message->setOrders($order);
        $chat_message->setDistributor($distributor_repo);
        $chat_message->setMessage($message);
        $chat_message->setIsDistributor($is_distributor);
        $chat_message->setIsClinic($is_clinic);

        $this->em->persist($chat_message);

        $chat_messages = $this->em->getRepository(ChatMessages::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);

        $distributor = false;
        $clinic = false;

        if($is_distributor == 1){

            $distributor = true;
        }

        if($is_clinic == 1){

            $clinic = true;
        }

        $messages = $this->getMessages($chat_messages, $date_sent,$distributor,$clinic)->getContent();

        // In app alert
        $notification = new Notifications();

        $notification->setClinic($clinic_repo);
        $notification->setDistributor($distributor_repo);
        $notification->setIsMessage(1);
        $notification->setIsTracking(0);
        $notification->setIsOrder(0);
        $notification->setIsRead(0);
        $notification->setIsActive(1);
        $notification->setOrders($order);

        $this->em->persist($notification);
        $this->em->flush();

        $message = '
        <table class="w-100">
            <tr>
                <td>
                    <a 
                        href="#"
                        data-order-id="'. $order_id .'"
                        data-distributor-id="'. $distributor_id .'"
                        data-clinic-id="'. $clinic_repo->getId() .'"
                        data-notification-id="'. $notification->getId() .'"
                        class="order_notification_alert"
                    >
                        <span class="badge bg-success me-3">New Message</span>
                    </a>
                </td>
                <td>
                    <a 
                        href="#"
                        data-order-id="'. $order_id .'"
                        data-distributor-id="'. $distributor_id .'"
                        data-clinic-id="'. $clinic_repo->getId() .'"
                        data-notification-id="'. $notification->getId() .'"
                        class="order_notification_alert"
                    >
                        PO No. '. $distributor_repo->getPoNumberPrefix() .'-'. $order_id .'
                    </a>
                </td>
                <td>
                    <a 
                        href="#" class="delete-notification" 
                        data-notification-id="'. $notification->getId() .'"
                        data-order-id="'. $order_id .'"
                        data-distributor-id="'. $distributor_id .'"
                    >
                        <i class="fa-solid fa-xmark text-black-25 ms-3 float-end"></i>
                    </a>
                </td>
            </tr>
        </table>';

        $notification->setNotification($message);

        $this->em->persist($notification);
        $this->em->flush();


        return new JsonResponse($messages);
    }

    public function getMessages($chat_messages, $date_sent,$distributor,$clinic){

        $messages = '<div style="height: 300px;overflow-x: hidden; overflow-y: auto; padding-bottom: 15px" id="distributor_chat_inner">';

        foreach($chat_messages as $chat){

            $type = false;

            if($distributor){

                $type = $chat->getIsDistributor();

            } elseif($clinic){

                $type = $chat->getIsClinic();
            }

            if($type){

                $class = 'speech-bubble-right p-3 mt-2 mb-2 me-1 float-end';

            } else {

                $class = 'speech-bubble-left p-3 mt-2 mb-2 ms-1 float-start';
            }

            if($date_sent != $chat->getCreated()->format('D dS M')){

                $messages .= '
                <div class="row">
                    <div class="col-12 text-center mb-2 mt-2">
                        <span class="badge badge-light p-1">
                            '. $chat->getCreated()->format('D dS M') .'
                        </span>
                    </div>
                </div>';
            }

            $date_sent = $chat->getCreated()->format('D dS M');

            $messages .= '
            <div class="row ps-3" style="width: calc(100% - 5px)">
                <div class="col-12">
                    <span class="'. $class .'">
                        '. $chat->getMessage() .'
                        <div class="text-end pt-1">'. $chat->getCreated()->format('H:i') .'</div>
                    </span>
                </div>
            </div>';
        }

        $messages .= '
            <div 
                class="ms-3 snippet position-absolute" 
                data-title=".dot-pulse" 
                id="chat_pulse"
                style="left: 5px;bottom: 5px"
            >
                <div class="stage">
                    <div class="dot-pulse"></div>
                </div>
            </div>
        </div>';

        return new Response($messages);
    }

    #[Route('/message/is_typing', name: 'is_typing')]
    public function sendIsTypingMessageAction(Request $request): Response
    {
        $data = $request->request;
        $order_id = $data->get('order_id');
        $distributor_id = $data->get('distributor_id');
        $is_clinic = $data->get('is_clinic');
        $is_distributor = $data->get('is_distributor');
        $is_typing = $data->get('is_typing');

        $chat_participants = $this->em->getRepository(ChatParticipants::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);

        if ($is_distributor == 1 && $distributor_id > 0) {

            $chat_participants->setDistributorIsTyping($is_typing);

        } elseif ($is_clinic == 1 && $distributor_id > 0) {

            $chat_participants->setClinicIsTyping($is_typing);

        } else {

            return new JsonResponse(false);
        }

        $this->em->persist($chat_participants);
        $this->em->flush();

        $response = [
            'is_typing' => $is_typing,
            'clinic_is_typing' => $chat_participants->getClinicIsTyping(),
            'distributor_is_typing' => $chat_participants->getDistributorIsTyping()
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/order/get-messages', name: 'get_messages')]
    public function distributorGetMessageAction(Request $request): Response
    {
        $clinic = $request->get('clinic');
        $distributor = $request->get('distributor');
        $distributor_id = $request->get('distributor_id');
        $order_id = $request->get('order_id');
        $total_messages = $request->get('total_messages');
        $is_typing = 0;
        $date_sent = '';
        $chat_messages = $this->em->getRepository(ChatMessages::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);

        $chat_participants = $this->em->getRepository(ChatParticipants::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);

        if($clinic){

            $is_typing = $chat_participants->getDistributorIsTyping();

        } elseif($distributor){

            $is_typing = $chat_participants->getClinicIsTyping();

        }

        // Only refresh chat if new messages
        $messages = '';

        if($total_messages < count($chat_messages)){

            $is_clinic = false;
            $is_distributor = false;

            if($clinic == 1){

                $is_clinic = true;
            }

            if($distributor == 1){

                $is_distributor = true;
            }

            $messages = $this->getMessages($chat_messages, $date_sent,$is_distributor,$is_clinic)->getContent();
        }

        $response = [
            'is_typing' => $is_typing,
            'messages' => $messages,
            'totals' => $total_messages .' - '. count($chat_messages)
        ];

        return new JsonResponse($response);
    }
}
