<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Baskets;
use App\Entity\ChatMessages;
use App\Entity\ChatParticipants;
use App\Entity\Clinics;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\Notifications;
use App\Entity\OrderItems;
use App\Entity\Orders;
use App\Entity\OrderStatus;
use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class OrdersController extends AbstractController
{
    private $em;
    private $mailer;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    #[Route('/clinics/checkout/options', name: 'checkout_options')]
    public function getCheckoutOptionsAction(Request $request): Response
    {
        $basket_id = $request->request->get('basket_id') ?? 0;
        $order = $this->em->getRepository(Orders::class)->findOneBy([
            'basket' => $basket_id,
        ]);
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $clinic = $this->getUser()->getClinic();
        $shipping_addresses  = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $clinic->getId(),
            'isActive' => 1,
            'type' => 2
        ]);
        $default_address = $this->em->getRepository(Addresses::class)->findOneBy([
            'clinic' => $clinic->getId(),
            'isDefault' => 1,
        ]);

        $response['default_address_id'] = $default_address->getId();

        // Create / update orders
        if($order == null){

            $order = new Orders();
        }

        $delivery_fee = 0;
        $sub_total = $basket->getTotal();
        $tax = 0;

        $order->setBasket($basket);
        $order->setClinic($clinic);
        $order->setStatus('checkout');
        $order->setDeliveryFee($delivery_fee);
        $order->setSubTotal($sub_total);
        $order->setTax($tax);
        $order->setTotal($delivery_fee + $sub_total + $tax);
        $order->setEmail($clinic->getEmail());

        $this->em->persist($order);
        $this->em->flush();

        $response['order_id'] = $order->getId();
        $purchase_orders = $this->em->getRepository(Distributors::class)->findByOrderId($order->getId());
        $order_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order->getId(),
        ]);
        $order_status = $this->em->getRepository(OrderStatus::class)->findBy([
            'orders' => $order->getId(),

        ]);
        $distributor_id = '';

        // Remove any previous items
        if(count($order_items) > 0){

            foreach($order_items as $order_item){

                $this->em->remove($order_item);
            }
        }

        // Remove previous status
        if(count($order_status) > 0){

            foreach($order_status as $status){

                $this->em->remove($status);
            }
        }

        // Create new order items
        if(count($basket->getBasketItems()) > 0){

            foreach($basket->getBasketItems() as $basket_item){

                // Generate PO prefix if one isn't yet set
                $distributor = $this->em->getRepository(Distributors::class)->find($basket_item->getDistributor()->getId());

                $prefix = $distributor->getPoNumberPrefix();

                if($prefix == null){

                    $words = preg_split("/\s+/", $distributor->getDistributorName());
                    $prefix = '';

                    foreach($words as $word){

                        $prefix .= substr(ucwords($word), 0, 1);
                    }

                    $distributor->setPoNumberPrefix($prefix);

                    $this->em->persist($distributor);
                }

                $order_items = new OrderItems();

                $order_items->setOrders($order);
                $order_items->setDistributor($basket_item->getDistributor());
                $order_items->setProduct($basket_item->getProduct());
                $order_items->setUnitPrice($basket_item->getUnitPrice());
                $order_items->setQuantity($basket_item->getQty());
                $order_items->setTotal($basket_item->getTotal());
                $order_items->setName($basket_item->getName());
                $order_items->setPoNumber($prefix .'-'. $order->getId());
                $order_items->setIsAccepted(0);
                $order_items->setIsRenegotiate(0);
                $order_items->setIsCancelled(0);
                $order_items->setIsConfirmedDistributor(0);
                $order_items->setStatus('Pending');

                $this->em->persist($order_items);

                // Order Status
                if($distributor_id != $basket_item->getDistributor()->getId()){

                    $distributor_id = $basket_item->getDistributor()->getId();

                    $order_status = new OrderStatus();

                    $order_status->setOrders($order);
                    $order_status->setDistributor($basket_item->getDistributor());
                    $order_status->setStatus('Pending');

                    $this->em->persist($order_status);
                }
            }

            $this->em->flush();
        }

        $plural = '';

        if(count($purchase_orders) > 1){

            $plural = 's';
        }

        $response['header'] = '
        <h4 class="text-white">Fluid Checkout</h4>
        <span class="text-white">
            Select shipping and payment options
        </span>';

        $response['body'] = '
        <form id="form_checkout_options" name="form_checkout_options" method="post">
            <input type="hidden" name="order-id" value="'. $order->getId() .'">
            <div class="row mt-4">
                <div class="col-12">
                    <h5>Fluid Account</h5>
                    <div class="alert alert-secondary" role="alert">
                        <div class="row border-bottom-dashed border-dark mb-3 pb-3">
                            <div class="col-6">
                                Account ID
                            </div>
                            <div class="col-6 text-end">
                                '. $clinic->getId() .'
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                Name
                            </div>
                            <div class="col-6 text-end">
                                '. $clinic->getClinicName() .'
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- PO Number -->
            <div class="row mt-3">
                <div class="col-12">
                    <h5>PO Number'. $plural .'</h5>
                    <div class="alert alert-secondary" role="alert">';

                    $i = 0;

                    foreach($purchase_orders as $po) {

                        $css = '';
                        $i++;

                        if($i != count($purchase_orders)){

                            $css = 'border-bottom-dashed border-dark mb-3 pb-3';
                        }

                        $response['body'] .= '
                        <div class="row '. $css .'">
                            <div class="col-6">
                                ' . $po->getDistributorName() . '
                            </div>
                            <div class="col-6 text-end">
                                ' . $po->getPoNumberPrefix() . '-' . $order->getId() . '
                            </div>
                        </div>';
                    }

                    $response['body'] .= '
                    </div>
                </div>
            </div>
            <!-- Email Address -->
            <div class="row mt-3">
                <div class="col-12">
                    <h5>Confirmation Email*</h5>
                    <input 
                        type="email" 
                        name="confirmation_email"
                        id="confirmation_email"
                        class="form-control alert alert-secondary" 
                        value="'. $clinic->getEmail() .'"
                    >
                </div>
                <div class="hidden_msg" id="error_confirmation_email">
                    Required Field
                </div>
            </div>
            <!-- Shipping Address -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="row">
                        <div class="col-6">
                            <h5>Shipping Address*</h5>
                        </div>
                        <div class="col-6 text-end">
                            <a 
                                href="" class="float-end" 
                                data-bs-toggle="modal"
                                data-bs-target="#modal_shipping_address"
                                id="link_shipping_address_modal"
                            >
                                Change Address
                            </a>
                        </div>
                    </div>
                    <div class="form-control alert alert-secondary" rows="4" name="address_shipping" id="checkout_shipping_address">'.
                        $default_address->getAddress() . '<br>' .
                        $default_address->getCity() .'<br>'.
                        $default_address->getPostalCode() .'<br>'.
                        $default_address->getState() .'
                    </div>
                    <input type="hidden" name="shipping_address_id" id="shipping_address_id" value="">
                    <input type="hidden" name="type" value="2">
                    <div class="hidden_msg" id="error_shipping_address">
                        Required Field
                    </div>
                </div>
            </div>
        
            <!-- Billing Address -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="row">
                        <div class="col-6">
                            <h5>Billing Address*</h5>
                        </div>
                        <div class="col-6 text-end">
                            <a 
                                href="" class="float-end"
                                data-bs-toggle="modal" data-bs-target="#modal_billing_address"
                                id="link_billing_address_modal"
                            >
                                Change Address
                            </a>
                        </div>
                    </div>
                    <div class="form-control alert alert-secondary" rows="4" name="address_billing" id="checkout_billing_address">'.
                        $default_address->getAddress() . '<br>' .
                        $default_address->getCity() .'<br>'.
                        $default_address->getPostalCode() .'<br>'.
                        $default_address->getState() .'
                    </div>
                    <input type="hidden" id="billing_address_id" name="billing_address_id" value="">
                    <input type="hidden" name="type" value="1">
                    <div class="hidden_msg" id="error_billing_address">
                        Required Field
                    </div>
                </div>
            </div>
            <!-- Additional Notes -->
            <div class="row mt-3">
                <div class="col-12">
                    <h5>Additional Notes</h5>
                    <div class="info mb-2">Add any special instructions with this order</div>
                    <textarea class="form-control alert alert-secondary" name="notes">'. $order->getNotes() .'</textarea>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-12">
                    <button 
                        type="submit"
                        class="btn btn-primary float-end" 
                        id="btn_order_review" 
                        data-order-id="5">
                            REVIEW ORDER 
                            <i class="fa-solid fa-circle-right ps-2"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Modal Manage Shipping Address -->
        <div class="modal fade" id="modal_shipping_address" tabindex="-1" aria-labelledby="address_delete_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form name="form_addresses_shipping_checkout" id="form_addresses_shipping_checkout" method="post">
                        <input type="hidden" value="'. $order->getId() .'" name="checkout">
                        <div id="shipping_address_modal"></div>
                    </form>
                </div>
            </div>
        </div>
       
        <!-- Modal Manage Billing Address -->
        <div class="modal fade" id="modal_billing_address" tabindex="-1" aria-labelledby="address_delete_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form name="form_addresses_billing_checkout" id="form_addresses_billing_checkout" method="post">
                        <input type="hidden" value="'. $order->getId() .'" name="checkout">
                        <div id="billing_address_modal"></div>
                    </form>
                </div>
            </div>
        </div>';

        $response['existing_shipping_addresses'] = '';
        $i = 0;

        foreach($shipping_addresses as $address){

            $i++;
            $margin_top = '';

            if($i == 1){

                $margin_top = 'mt-3';
            }

            $response['existing_shipping_addresses'] .= '
            <div class="row '. $margin_top .'">
                <div class="col-12">
                    <input 
                        type="radio" 
                        name="address" 
                        class="btn-check existing-address" 
                        value="'. $address->getId() .'" 
                        id="address_'. $i .'" 
                        autocomplete="off"
                    >
                    <label class="btn btn-outline-primary alert alert-secondary w-100" for="address_'. $i .'">'.
                        $address->getAddress() .' '. $address->getCity() .' '. $address->getPostalCode() .' '.
                        $address->getState() .'
                    </label>
                </div>
            </div>';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/checkout/save/options', name: 'checkout_save_options')]
    public function saveCheckoutOptionsAction(Request $request): Response
    {
        $data = $request->request;
        $clinic = $this->getUser()->getClinic();
        $order = $this->em->getRepository(Orders::class)->find($data->get('order-id'));
        $response = '';

        if($order != null){

            $shipping_address = $this->em->getRepository(Addresses::class)->find($data->get('shipping_address_id'));
            $billing_address = $this->em->getRepository(Addresses::class)->find($data->get('billing_address_id'));
            $purchase_orders = $this->em->getRepository(Distributors::class)->findByOrderId($order->getId());
            $basket = $order->getBasket();

            $plural = '';

            if(count($purchase_orders) > 1){

                $plural = 's';
            }

            // Update order
            $order->setEmail($data->get('confirmation_email'));
            $order->setAddress($shipping_address);
            $order->setBillingAddress($billing_address);

            if($data->get('notes') != null){

                $order->setNotes($data->get('notes'));
            }

            $this->em->persist($order);
            $this->em->flush();

            $suite_delivery = '';
            $suite_billing = '';

            if(strlen($order->getAddress()->getSuite()) > 0){

                $suite_delivery = $basket->getOrders()->getAddress()->getSuite() .'<br>';
            }

            if(strlen($order->getBillingAddress()->getSuite()) > 0){

                $suite_billing = $basket->getOrders()->getBillingAddress()->getSuite() .'<br>';
            }

            // Order Review
            $response .= '
            <div class="row">
                <div class="col-12 col-sm-6 mt-2">
                    <div class="alert alert-secondary">
                        <b class="text-primary">Account ID:</b> <span class="float-end">'. $clinic->getId() .'</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 mt-2">
                    <div class="alert alert-secondary">
                        <b class="text-primary">Name:</b> <span class="float-end">'. $clinic->getClinicName() .'</span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-6 mt-2">
                    <div class="alert alert-secondary">
                        <b class="text-primary">Telephone:</b> <span class="float-end">'. $order->getClinic()->getTelephone() .'</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 mt-2">
                    <div class="alert alert-secondary">
                        <b class="text-primary">Confirmation Email:</b> <span class="float-end">'. $order->getEmail() .'</span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-col-12 col-sm-6 mt-2">
                    <div class="alert alert-secondary">
                        <div class="text-primary mb-3 fw-bold">Shipping Address</div>
                        '. $basket->getOrders()->getAddress()->getClinicName() .'<br>
                        '. $basket->getOrders()->getAddress()->getAddress() .'<br>
                        '. $suite_delivery .'
                        '. $basket->getOrders()->getAddress()->getCity() .'<br>
                        '. $basket->getOrders()->getAddress()->getPostalCode() .'<br>
                        '. $basket->getOrders()->getAddress()->getState() .'<br><br>
                        <span class="fw-bold text-primary">Telephone :</span> '. $basket->getOrders()->getAddress()->getTelephone() .'
                    </div>
                </div>
                <div class="col-12 col-sm-6 mt-2">
                    <div class="alert alert-secondary">
                        <div class="text-primary mb-3 fw-bold">Billing Address</div>
                        '. $basket->getOrders()->getBillingAddress()->getClinicName() .'<br>
                        '. $basket->getOrders()->getBillingAddress()->getAddress() .'<br>
                        '. $suite_billing .'
                        '. $basket->getOrders()->getBillingAddress()->getCity() .'<br>
                        '. $basket->getOrders()->getBillingAddress()->getPostalCode() .'<br>
                        '. $basket->getOrders()->getBillingAddress()->getState() .'<br><br>
                        <span class="fw-bold text-primary">Telephone :</span> '. $basket->getOrders()->getBillingAddress()->getTelephone() .'
                    </div>
                </div>
            </div>';

            // Additional notes
            if(!empty($data->get('notes'))){

                $response .= '
                <div class="row">
                    <div class="col-12 mt-2">
                        <div class="alert alert-secondary">
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-primary mb-3 fw-bold">Additional Notes</div>
                                    '. $data->get('notes') .'
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
            }

            // Purchase orders
            $i = 0;

            $response .= '
            <div class="row">
                    <div class="col-12 mt-2">
                        <div class="alert alert-secondary">
                            <div class="text-primary mb-3 fw-bold border-bottom-dashed border-dark mb-3 pb-3">
                                PO Number'. $plural .'
                            </div>';

            foreach($purchase_orders as $po){

                $css = '';
                $i++;

                if(count($purchase_orders) != $i){

                    $css = 'border-bottom-dashed border-dark mb-3 pb-3';
                }

                $response .= '
                
                            <div class="row '. $css .'">
                                <div class="col-12 col-sm-6">
                                    '. $po->getDistributorName() .'
                                </div>
                                <div class="col-12 col-sm-6 text-end">
                                    '. $po->getPoNumberPrefix() .'-'. $order->getId() .'
                                </div>
                            </div>';
            }

            $response .= '
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mt-2">
                    <div class="alert alert-secondary">';

            foreach($basket->getBasketItems() as $item){

                $distributor_product = $this->em->getRepository(DistributorProducts::class)->findOneBy([
                    'product' => $item->getProduct()->getId(),
                    'distributor' => $item->getDistributor()->getId(),
                ]);

                $response .= '
                <div class="row">
                    <!-- Thumbnail -->
                    <div class="col-12 col-sm-2 text-center pt-3">
                        <img class="img-fluid basket-img" src="/images/products/' . $item->getProduct()->getImage() . '" style="max-height: 45px">
                    </div>
                    <div class="col-12 col-sm-10 pt-3">
                        <!-- Product Name and Qty -->
                        <div class="row">
                            <!-- Product Name -->
                            <div class="col-12 col-sm-7">
                                <span class="info">'. $item->getDistributor()->getDistributorName() .'</span>
                                <h6 class="fw-bold text-center text-sm-start text-primary mb-0">
                                    ' . $item->getProduct()->getName() . ': ' . $item->getProduct()->getDosage() . ' ' . $item->getProduct()->getUnit() . '
                                </h6>
                            </div>
                            <!-- Product Quantity -->
                            <div class="col-12 col-sm-5 d-table">
                                <div class="row d-table-row">
                                    <div class="col-4 text-center text-sm-start d-table-cell align-bottom">
                                        $' . number_format($distributor_product->getUnitPrice(),2) . '
                                    </div>
                                    <div class="col-4 text-center d-table-cell align-bottom">
                                        ' . $item->getQty() . '
                                    </div>
                                    <div class="col-4 text-center text-sm-start fw-bold d-table-cell align-bottom">$' . number_format($item->getTotal(),2) . '</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
            }

            $response .= '
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary float-end" id="btn_place_order" data-order-id="'. $order->getId() .'">
                        PLACE ORDER 
                        <i class="fa-solid fa-circle-right ps-2"></i>
                    </button>
                </div>
            </div>';

        } else {


        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/checkout/place/order', name: 'checkout_place_order')]
    public function placeOrderAction(Request $request, MailerInterface $mailer): Response
    {
        $order_id = $request->request->get('order_id');
        $order_distributors = $this->em->getRepository(OrderItems::class)->findOrderDistributors($order_id);
        $order = $this->em->getRepository(Orders::class)->find($order_id);

        foreach($order_distributors as $distributor){

            $order_items = $this->em->getRepository(OrderItems::class)->findBy([
                'orders' => $order_id,
                'distributor' => $distributor->getDistributor()->getId(),
            ]);

            $email_address = $order_items[0]->getDistributor()->getEmail();
            $clinic = $this->getUser()->getClinic();
            $clinic_email = $clinic->getEmail();
            $subject = 'Fluid Order';
            $distributor_name = $order_items[0]->getDistributor()->getDistributorName();
            $po_number = $order_items[0]->getPoNumber();
            $order_url = 'https://127.0.0.1:8000/distributors/order/'. $order_items[0]->getOrders()->getId();
            $i = 0;

            $rows = '
            <table style="border: none; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px; width: 100%">
                <tr>
                    <th style="border: solid 1px #ccc; background: #ccc">#</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Name</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Price</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Qty</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Total</th>
                </tr>';

            foreach($order_items as $item){

                $i++;

                $rows .= '
                <tr>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $i .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getName() .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getUnitPrice() .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getQuantity() .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getTotal() .'</td>
                </tr>';
            }

            $rows .= '</table>';

            $body = '
            <table style="border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px; width: 700px;">
                <tr>
                    <td colspan="2">
                        Please <a href="'. $order_url .'">click here</a> in order to login in to your Fluid account to manage this order
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td>
                        '. $distributor_name .'
                    </td>
                    <td align="right" rowspan="2">
                        <span style="font-size: 24px">
                            PO Number: '. $po_number .'
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        '. $clinic_email .'
                    </td>
                </tr>
                <tr>
                    <td align="right">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        '. $rows .'
                    </td>
                </tr>
            </table>';

            $email = (new Email())
                ->from($this->getParameter('app.email_from'))
                ->addTo($email_address)
                ->subject($subject)
                ->html($body);

            $mailer->send($email);

            // Update order status
            $order->setStatus('submitted');

            $this->em->persist($order);

            // Initiate IM
            $chat_participants = $this->em->getRepository(ChatParticipants::class)->findOneBy([
                'distributor' => $distributor->getId(),
                'clinic' => $clinic->getId(),
                'orders' => $order_id
            ]);

            if($chat_participants == null){

                // Create Chat
                $chat_participants = new ChatParticipants();

                $chat_participants->setDistributor($distributor->getDistributor());
                $chat_participants->setClinic($clinic);
                $chat_participants->setOrders($order_items[0]->getOrders());
                $chat_participants->setDistributorIsTyping(0);
                $chat_participants->setClinicIsTyping(0);

                $this->em->persist($chat_participants);
            }
        }

        // Close the basket
        $basket = $this->em->getRepository(Baskets::class)->find($order_items[0]->getOrders()->getBasket()->getId());

        $basket->setStatus('closed');

        $this->em->persist($basket);

        $basket_new = new Baskets();

        $basket_new->setClinic($clinic);
        $basket_new->setStatus('active');
        $basket_new->setName($basket->getName());
        $basket_new->setTotal(0.00);
        $basket_new->setIsDefault(1);
        $basket_new->setSavedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($basket_new);
        $this->em->flush();

        $flash = '<b><i class="fa-solid fa-circle-check"></i></i></b> Order successfully placed.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        $orders = $this->forward('App\Controller\OrdersController::clinicGetOrdersAction')->getContent();

        $response = [
            'flash' => $flash,
            'orders' => json_decode($orders)
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/update-order', name: 'distributor_update_order')]
    public function distributorUpdateOrderAction(Request $request, MailerInterface $mailer): Response
    {
        $data = $request->request;
        $order_id = (int) $data->get('order_id');
        $expiry_dates = $data->get('expiry_date');
        $prices = $data->get('price');
        $quantities = $data->get('qty');
        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $distributor = $this->getUser()->getDistributor();
        $product_id = $data->get('product_id');

        if($product_id != null && count($product_id) > 0){

            for($i = 0; $i < count($product_id); $i++){

                $product = $this->em->getRepository(Products::class)->find($product_id[$i]);
                $order_item = $this->em->getRepository(OrderItems::class)->findOneBy([
                    'product' => $product_id[$i],
                    'orders' => $order_id,
                    'distributor' => $distributor->getId()
                ]);

                if($expiry_dates[$i] != 0) {

                    $order_item->setExpiryDate(\DateTime::createFromFormat('Y-m-d', $expiry_dates[$i]));
                }

                $order_item->setUnitPrice($prices[$i]);
                $order_item->setQuantity($quantities[$i]);
                $order_item->setTotal($prices[$i] * $quantities[$i]);

                $this->em->persist($order_item);
            }

            $sum_total = $this->em->getRepository(OrderItems::class)->findSumTotalOrderItems($order_id, $distributor->getId());

            $order->setSubTotal($sum_total[0]['totals']);
            $order->setTotal($sum_total[0]['totals'] +  + $order->getDeliveryFee() + $order->getTax());

            $this->em->persist($order);
            $this->em->flush();
        }

        $flash = '<b><i class="fa-solid fa-circle-check"></i></i></b> Order successfully saved.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'order_id' => $order_id,
            'distributor_id' => $distributor->getId(),
            'clinic_id' => $order->getClinic()->getId(),
            'flash' => $flash
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/send-order-notification', name: 'distributor_send_order_notification')]
    public function distributorSendOrderNotificationAction(Request $request, MailerInterface $mailer): Response
    {
        $data = $request->request;
        $order_id = $data->get('order_id');
        $distributor_id = $data->get('distributor_id');
        $clinic_id = $data->get('clinic_id');
        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);

        // Clinic in app notification
        $notification = new Notifications();

        $notification->setClinic($order->getClinic());
        $notification->setIsActive(1);
        $notification->setIsRead(0);
        $notification->setOrders($order);
        $notification->setDistributor($distributor);
        $notification->setIsOrder(1);
        $notification->setIsTracking(0);
        $notification->setIsMessage(0);

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
                        data-clinic-id="'. $clinic_id .'"
                        class="order_notification_alert"
                    >
                        <span class="badge bg-success me-3">Order Update</span>
                    </a>
                </td>
                <td>
                    <a 
                        href="#"
                        data-order-id="'. $order_id .'"
                        data-distributor-id="'. $distributor_id .'"
                        data-clinic-id="'. $clinic_id .'"
                        class="order_notification_alert"
                    >
                        PO No. '. $distributor->getPoNumberPrefix() .'-'. $order_id .'
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

        // Send Email Notification
        $this->sendOrderEmail($order_id, $distributor_id, $clinic_id, 'clinics');

        $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Order successfully saved.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/distributors/order', name: 'distributor_get_order_details')]
    public function distributorOrderDetailAction(Request $request): Response
    {
        $order_id = $request->request->get('order_id');
        $distributor = $this->getUser()->getDistributor();
        $chat_messages = $this->em->getRepository(ChatMessages::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor->getId()
        ]);
        $orders = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor->getId()

        ]);
        $date_sent = '';
        $messages = $this->forward('App\Controller\ChatMessagesController::getMessages', [
            'chat_messages' => $chat_messages,
            'date_sent' => $date_sent,
            'distributor' => true,
            'clinic' => false,
        ])->getContent();

        $response = '
        <form name="form_distributor_orders" id="form_distributor_orders" method="post">
            <input type="hidden" name="order_id" value="'. $orders[0]->getOrders()->getId() .'">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center mt-5 pt-3 pb-3" id="order_header">
                        <h4 class="text-white">'. $orders[0]->getPoNumber() .'</h4>
                        <span class="text-white">
                            '. $orders[0]->getOrders()->getClinic()->getClinicName() .'
                        </span>
                    </div>
                </div>
                <!-- Actions Row -->
                <div class="row" id="order_action_row_1">
                    <div class="col-12 d-flex justify-content-center border-bottom pt-3 pb-3 bg-light border-left border-right">
                        <a 
                            href="#" 
                            class="refresh-distributor-order" 
                            data-order-id="'. $order_id .'"
                            data-distributor-id="'. $distributor->getId() .'"
                            data-clinic-id="'. $orders[0]->getOrders()->getClinic()->getId() .'"
                        >
                            <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i>
                            <span class=" d-none d-md-inline-block pe-4">Refresh Order</span>
                        </a>
                        <button type="submit" class="saved_baskets_link btn btn-sm btn-light p-0 text-primary">
                            <i class="fa-solid fa-floppy-disk me-5  me-md-2"></i>
                            <span class=" d-none d-md-inline-block pe-4">Save Order</span>
                        </button>
                        <a 
                            href="#" 
                            id="order_send_notification"
                            data-order-id="'. $order_id .'"
                            data-distributor-id="'. $orders[0]->getDistributor()->getId() .'"
                            data-clinic-id="'. $orders[0]->getOrders()->getClinic()->getId() .'"
                        >
                            <i class="fa-solid fa-paper-plane me-0 me-md-2"></i><span class=" d-none d-md-inline-block pe-4">Send Notification</span>
                        </a>
                    </div>
                </div>
                <!-- Products -->
                <div class="row border-0 bg-light">
                    <div class="col-12 col-md-9 border-right col-cell border-left border-right border-bottom">
                        <input type="hidden" name="distributor_id" value="'. $distributor->getId() .'">';

        foreach($orders as $order) {

            $expiry = '';

            if(!empty($order->getExpiryDate())){

                $expiry = $order->getExpiryDate()->format('Y-m-d');
            }

            // Item status
            $disabled = '';
            $opacity = '';
            $badge_cancelled = '';
            $badge_confirm = '';
            $badge_pending = '';
            $clinic_status = '';

            if($order->getIsCancelled() == 1){

                $disabled = 'disabled';
                $opacity = 'opacity-50';

                $badge_cancelled = '
                <span
                    class="badge float-end ms-2 text-light border border-danger text-light order_item_accept bg-danger"
                >Cancelled</span>';

            } else {

                if ($order->getIsConfirmedDistributor() == 1) {

                    if($order->getIsAccepted() == 1){

                        $disabled = 'disabled';

                        $clinic_status = '
                        <span 
                            class="badge float-end ms-2 text-light border border-success bg-success"
                        >Accepted</span>';

                    } elseif($order->getIsRenegotiate() == 1) {

                        $clinic_status = '
                        <span 
                            class="badge float-end ms-2 text-light border border-warning bg-warning"
                        >Renegotiating</span>';

                    } else {

                        $badge_pending = '
                        <a href="#" 
                            class="badge float-end ms-2 border-1 badge-pending-outline-only btn_pending"
                            data-order-id="' . $order_id . '"
                            data-item-id="' . $order->getId() . '"
                        >Pending</a>';

                            $badge_confirm = '
                        <span 
                            class="badge float-end ms-2 text-light border border-success bg-success"
                        >Confirmed</span>';
                    }

                } else {

                    $badge_pending = '
                    <span 
                        class="badge float-end ms-2 text-light border-1 bg-dark-grey border-dark-grey"
                    >Pending</span>';

                    $badge_confirm = '
                    <a href="#" 
                        class="badge float-end ms-2 text-success border-1 badge-success-outline-only btn_confirm"
                        data-order-id="' . $order_id . '"
                        data-item-id="' . $order->getId() . '"
                    >Confirm</a>';
                }
            }

            $prd_id = '<input type="hidden" name="product_id[]" value="'. $order->getProduct()->getId() .'" '. $disabled .'>';
            $expiry_date_required = $order->getProduct()->getExpiryDateRequired();

            if($expiry_date_required) {

                $expiry_date = '
                <input 
                    placeholder="Expiry Date" 
                    name="expiry_date[]"
                    class="form-control form-control-sm ' . $opacity . '" 
                    type="text" 
                    onfocus="(this.type=\'date\')" 
                    id="date"
                    value="' . $expiry . '"
                     ' . $disabled . '
                >';
            } else {

                $expiry_date = '
                <input 
                    name="expiry_date[]"
                    type="hidden" 
                    value="0">';
            }
            $unit_price = '
            <input 
                type="text" 
                name="price[]" 
                value="'. number_format($order->getUnitPrice(),2) .'"
                class="form-control form-control-sm '. $opacity .'"
                 '. $disabled .'
            >';
            $qty = '
            <input 
                type="text" 
                name="qty[]" 
                class="form-control basket-qty form-control-sm text-center '. $opacity .'" 
                value="'. $order->getQuantity() .'" 
                 '. $disabled .'
            />';

            // Remove form fields once accepted
            if($order->getIsAccepted() == 1 || $order->getIsCancelled()){

                if($order->getIsCancelled() == 1){

                    $opacity = 'opacity-50';
                }

                if($order->getExpiryDate() != null) {

                    $expiry_date = '<span class="'. $opacity .'">'. $order->getExpiryDate()->format('Y-m-d') .'</span>';
                }

                $unit_price = '<span class="'. $opacity .'">$'. number_format($order->getUnitPrice(),2). '</span>';
                $qty = '<span class="'. $opacity .'">'. $order->getQuantity() .'</span>';
            }

            $response .= '
                <!-- Product Name and Qty -->
                '. $prd_id .'
                <div class="row">
                    <!-- Product Name -->
                    <div class="col-12 col-sm-6 pt-3 pb-3">
                        <span class="info '. $opacity .'">'. $order->getDistributor()->getDistributorName() .'</span>
                        <h6 class="fw-bold text-center text-sm-start text-primary lh-base '. $opacity .'">
                            '. $order->getName() .'
                        </h6>
                    </div>
                    <!-- Expiry Date -->
                    <div class="col-12 col-sm-6 pt-3 pb-3 d-table">
                        <div class="row d-table-row">
                            <div class="col-6 text-center text-sm-end d-table-cell align-bottom">
                                '. $expiry_date .'
                                <div class="hidden_msg" id="error_expiry_date_'. $order->getProduct()->getId() .'">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-3 text-center d-table-cell align-bottom">
                                '. $unit_price .'
                                <div class="hidden_msg" id="error_price_'. $order->getProduct()->getId() .'">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-2 d-table-cell align-bottom">
                                '. $qty .'
                                <div class="hidden_msg" id="error_qty_'. $order->getProduct()->getId() .'">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-2 text-center text-sm-start fw-bold d-table-cell align-bottom '. $opacity .'">
                                $'. number_format($order->getUnitPrice() * $order->getQuantity(),2) .'
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Status -->
                <div class="row">
                    <div class="col-12">';

                        if(
                            ($order->getProduct()->getExpiryDateRequired() == 1 && $order->getExpiryDate() != null) ||
                            ($order->getProduct()->getExpiryDateRequired() == 0)
                        ) {

                            $response .=  $badge_confirm;
                        }

                        $response .= $badge_pending . $badge_cancelled . $clinic_status;

                        $response .= '
                            </div>
                        </div>';
        }

                    $response .= '    
                    </div>
                    <!-- Chat -->
                    <div class="col-12 col-md-3 col-cell p-0 border-bottom border-right">
                        <table class="table table-borderless h-100 mb-0">
                            <tr>
                                <td class="link-secondary table-primary border-bottom" style="height: 30px; background: #f4f8fe">
                                    '. $order->getOrders()->getClinic()->getClinicName() .'
                                </td>
                            </tr>
                            <tr>
                                <td 
                                    class="border-bottom position-relative p-0" 
                                    id="distributor_chat_container"
                                >
                                    '. $messages .'
                                </td>
                            </tr>
                            <tr>
                                <td style="height: 30px">
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            id="chat_field" 
                                            class="form-control form-control-sm border-0"  
                                            autocomplete="off"
                                            data-distributor-id="'. $orders[0]->getDistributor()->getId() .'"
                                            data-order-id="'. $order_id .'"
                                            data-clinic-id="0"
                                        />
                                        <button 
                                            type="button" 
                                            class="btn btn-light btn-sm chat-send" 
                                            id="btn_chat_send"
                                            data-order-id="'. $order_id .'"
                                            data-distributor-id="'. $orders[0]->getDistributor()->getId() .'"
                                        >
                                            <i class="fa-solid fa-paper-plane me-0 me-md-2 text-primary"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </form>';

        return new JsonResponse($response);
    }

    #[Route('/distributors/orders', name: 'distributor_get_order_list')]
    public function distributorGetOrdersAction(Request $request): Response
    {
        $distributor = $this->em->getRepository(Distributors::class)->find($this->getUser()->getDistributor()->getId());
        $orders = $this->em->getRepository(Orders::class)->findByDistributor($distributor);

        $response = '
        <form name="form_distributor_orders" id="form_distributor_orders" method="post">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center mt-5 pt-3 pb-3" id="order_header">
                        <h4 class="text-white">Manage Fluid Orders</h4>
                        <span class="text-white">
                            Manage All Your Orders In One Place
                        </span>
                    </div>
                </div>';

                if(count($orders) > 0) {

                    $response .= '
                    <!-- Actions Row -->
                    <div class="row" id="order_action_row_1">
                        <div class="col-12 d-flex justify-content-center border-bottom pt-3 pb-3 bg-light border-left border-right">
                            
                        </div>
                    </div>
                    <!-- Orders -->
                    <div class="row">
                        <div class="col-12 bg-light border-bottom border-right border-left">
                            <div class="row">
                                <div class="col-12 col-sm-1 pt-3 pb-3 text-primary fw-bold">
                                    #Id
                                </div>
                                <div class="col-12 col-sm-4 pt-3 pb-3 text-primary fw-bold">
                                    Clinic
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3 text-primary fw-bold">
                                    Total
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3 text-primary fw-bold">
                                    Date
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3 text-primary fw-bold">
                                    Status
                                </div>
                            </div>    
                        </div>
                    </div>';
                }

                $response .= '
                <div class="row">
                    <div class="col-12 border-right bg-light col-cell border-left border-right border-bottom">';

                    if(count($orders) > 0) {

                        foreach ($orders as $order) {

                            $response .= '
                            <!-- Orders -->
                            <div class="row">
                                <div class="col-12 col-sm-1 pt-3 pb-3">
                                    ' . $order->getId() . '
                                </div>
                                <div class="col-12 col-sm-4 pt-3 pb-3">
                                    ' . $order->getClinic()->getClinicName() . '
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3">
                                    $' . $order->getTotal() . '
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3">
                                    ' . $order->getCreated()->format('Y-m-d') . '
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3">
                                    ' . ucfirst($order->getOrderStatuses()[0]->getStatus()) . '
                                </div>
                                <div class="col-12 col-sm-1 pt-3 pb-3 text-end">
                                    <a 
                                        href="' . $this->getParameter('app.base_url') . '/distributors/order/' . $order->getId() . '" 
                                        class="pe-0 pe-sm-3 order_detail_link"
                                        data-order-id="' . $order->getId() . '"
                                        data-distributor-id="' . $distributor->getId() . '"
                                        data-clinic-id="' . $order->getClinic()->getId() . '"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </div>';
                        }

                    } else {

                        $response .= '
                        <div class="row">
                            <div class="col-12 text-center mt-5 mb-5 pt-3 pb-3 text-center">
                                You don\'t have any orders available. 
                            </div>
                        </div>';
                    }

                    $response .= '
                    </div>
                </div>
            </div>
        </form>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/order/', name: 'clinic_get_order_details')]
    public function clinicOrderDetailAction(Request $request): Response
    {
        $data = $request->request;
        $order_id = $data->get('order_id');
        $distributor_id = $data->get('distributor_id');
        $chat_messages = $this->em->getRepository(ChatMessages::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $orders = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id

        ]);
        $date_sent = '';
        $messages = $this->forward('App\Controller\ChatMessagesController::getMessages', [
            'chat_messages' => $chat_messages,
            'date_sent' => $date_sent,
            'distributor' => false,
            'clinic' => true,
        ])->getContent();

        $response = '
        <form name="form_distributor_orders" id="form_distributor_orders" method="post">
            <input type="hidden" name="order_id" value="'. $orders[0]->getOrders()->getId() .'">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3" id="order_header">
                        <h4 class="text-white">'. $orders[0]->getPoNumber() .'</h4>
                        <span class="text-white">
                            '. $orders[0]->getOrders()->getClinic()->getClinicName() .'
                        </span>
                    </div>
                </div>
                <!-- Actions Row -->
                <div class="row">
                    <div 
                        class="col-12 d-flex justify-content-center border-bottom pt-3 pb-3 bg-light border-left border-right"
                         id="order_action_row"
                    >
                        <a 
                            href="#" 
                            class="refresh-clinic-order" 
                            data-order-id="'. $order_id .'"
                            data-distributor-id="'. $distributor_id .'"
                            data-clinic-id="'. $orders[0]->getOrders()->getClinic()->getId() .'"
                        >
                            <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i>
                            <span class=" d-none d-md-inline-block pe-4">Refresh Order</span>
                        </a>
                        '. $this->btnConfirmOrder($orders, $order_id) .'
                    </div>
                </div>
                <!-- Products -->
                <div class="row border-0 bg-light">
                    <div class="col-12 col-md-9 border-right col-cell border-left border-right border-bottom">
                        <input type="hidden" name="distributor_id" value="'. $distributor_id .'">';

                        foreach($orders as $order) {

                            $expiry = '';

                            if(!empty($order->getExpiryDate())){

                                $expiry = $order->getExpiryDate()->format('Y-m-d');
                            }

                            // Status badges
                            if($order->getIsAccepted() == 1){

                                $badge_accept = 'bg-success';

                            } else {

                                $badge_accept = 'badge-success-outline-only';
                            }

                            if($order->getIsRenegotiate() == 1){

                                $badge_renegotiate = 'bg-warning';

                            } else {

                                $badge_renegotiate = 'badge-warning-outline-only';
                            }

                            if($order->getIsCancelled() == 1){

                                $badge_cancelled = 'bg-danger';

                            } else {

                                $badge_cancelled = 'badge-danger-outline-only';
                            }

                            $response .= '
                            <!-- Product Name and Qty -->
                            <div class="row">
                                <!-- Product Name -->
                                <div class="col-12 col-sm-6 pt-3 pb-3">
                                    <span class="info">'. $order->getDistributor()->getDistributorName() .'</span>
                                    <h6 class="fw-bold text-center text-sm-start text-primary lh-base">
                                        '. $order->getName() .'
                                    </h6>
                                </div>
                                <!-- Expiry Date -->
                                <div class="col-12 col-sm-6 pt-3 pb-3 d-table">
                                    <div class="row d-table-row">
                                        <div class="col-6 text-center text-sm-end d-table-cell align-bottom text-end alert-text-grey">
                                            '. $expiry .'
                                        </div>
                                        <div class="col-2 text-center d-table-cell align-bottom text-end alert-text-grey">
                                            $'. number_format($order->getUnitPrice(),2) .'
                                        </div>
                                        <div class="col-1 d-table-cell align-bottom text-end alert-text-grey">
                                            '. $order->getQuantity() .'
                                        </div>
                                        <div class="col-3 text-center text-sm-end fw-bold d-table-cell align-bottom alert-text-grey">
                                            $'. number_format($order->getUnitPrice() * $order->getQuantity(),2) .'
                                        </div>
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="col-12 pb-2">';

                                    if($order->getIsConfirmedDistributor() == 1) {

                                        $response .= '
                                        <a href="#" 
                                            class="badge float-end ms-2 text-success border-1 text-light order_item_accept ' . $badge_accept . '"
                                            data-order-id="' . $order_id . '"
                                            data-item-id="' . $order->getId() . '"
                                            id="order_item_accept_' . $order->getId() . '"
                                        >Accept</a>
                                        <a href="#" 
                                            class="badge float-end ms-2 text-warning border-1 text-light order_item_renegotiate ' . $badge_renegotiate . '"
                                            data-order-id="' . $order_id . '"
                                            data-item-id="' . $order->getId() . '"
                                            id="order_item_renegotiate_' . $order->getId() . '"
                                        >Renegotiate</a>
                                        <a href="#" 
                                            class="badge float-end text-light order_item_cancel ' . $badge_cancelled . '"
                                            data-order-id="' . $order_id . '"
                                            data-item-id="' . $order->getId() . '"
                                            id="order_item_cancel_' . $order->getId() . '"
                                        >Cancel</a>';

                                    } else {

                                        $response .= '<span class="badge bg-dark-grey float-end">Pending Distributor Confirmation</span>';
                                    }

                                $response .= '
                                </div>
                            </div>';
                        }

                    $response .= '    
                    </div>
                    <!-- Chat -->
                    <div class="col-12 col-md-3 col-cell p-0 border-bottom border-right">
                        <table class="table table-borderless h-100 mb-0">
                            <tr>
                                <td class="link-secondary table-primary border-bottom" style="height: 30px; background: #f4f8fe">
                                    '. $order->getOrders()->getClinic()->getClinicName() .'
                                </td>
                            </tr>
                            <tr>
                                <td 
                                    class="border-bottom position-relative p-0" 
                                    id="distributor_chat_container"
                                >
                                    '. $messages .'
                                </td>
                            </tr>
                            <tr>
                                <td style="height: 30px">
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            id="chat_field" 
                                            class="form-control form-control-sm border-0"  
                                            autocomplete="off"
                                            data-distributor-id="'. $distributor_id .'"
                                            data-order-id="'. $order_id .'"
                                            data-clinic-id="'. $orders[0]->getOrders()->getClinic()->getId() .'"
                                        />
                                        <button 
                                            type="button" 
                                            class="btn btn-light btn-sm chat-send" 
                                            id="btn_chat_send"
                                            data-order-id="'. $order_id .'"
                                            data-distributor-id="'. $distributor_id .'"
                                        >
                                            <i class="fa-solid fa-paper-plane me-0 me-md-2 text-primary"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </form>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/orders', name: 'clinic_get_order_list')]
    public function clinicGetOrdersAction(Request $request): Response
    {
        $clinic = $this->getUser()->getClinic();
        $orders = $this->em->getRepository(Orders::class)->findClinicOrders($clinic->getId());

        $response = '
        <form name="form_distributor_orders" id="form_distributor_orders" method="post">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3" id="order_header">
                        <h4 class="text-white">Manage Fluid Orders</h4>
                        <span class="text-white">
                            Manage All Your Orders In One Place
                        </span>
                    </div>
                </div>';

                if(count($orders) > 0) {

                    $response .= '
                    <!-- Orders -->
                    <div class="row">
                        <div class="col-12 bg-light border-bottom border-right border-left">
                            <div class="row">
                                <div class="col-12 col-sm-1 pt-3 pb-3 text-primary fw-bold">
                                    #Id
                                </div>
                                <div class="col-12 col-sm-4 pt-3 pb-3 text-primary fw-bold">
                                    Distributor
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3 text-primary fw-bold">
                                    Total
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3 text-primary fw-bold">
                                    Date
                                </div>
                                <div class="col-12 col-sm-2 pt-3 pb-3 text-primary fw-bold">
                                    Status
                                </div>
                            </div>    
                        </div>
                    </div>      
                    <div class="row">
                        <div class="col-12 border-right bg-light col-cell border-left border-right border-bottom">';

                    foreach ($orders as $order) {

                        $response .= '
                        <!-- Orders -->
                        <div class="row">
                            <div class="col-12 col-sm-1 pt-3 pb-3">
                                ' . $order['id'] . '
                            </div>
                            <div class="col-12 col-sm-4 pt-3 pb-3">
                                ' . $order['distributor_name'] . '
                            </div>
                            <div class="col-12 col-sm-2 pt-3 pb-3">
                                $' . $order['total'] . '
                            </div>
                            <div class="col-12 col-sm-2 pt-3 pb-3">
                                ' . $order['created'] . '
                            </div>
                            <div class="col-12 col-sm-2 pt-3 pb-3">
                                ' . ucfirst($order['status']) . '
                            </div>
                            <div class="col-12 col-sm-1 pt-3 pb-3 text-end">
                                <a 
                                    href="' . $this->getParameter('app.base_url') . '/clinics/order/' . $order['id'] . '/' . $order['distributor_id'] . '" 
                                    class="pe-0 pe-sm-3"
                                    id="order_detail_link"
                                    data-order-id="' . $order['id'] . '"
                                    data-distributor-id="' . $order['distributor_id'] . '"
                                    data-clinic-id="' . $order['clinic_id'] . '"
                                >
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </div>
                        </div>';
                    }
                } else {

                    $response .= '
                    <div class="row">
                        <div class="col-12 text-center mt-5 mb-5 pt-3 pb-3 text-center">
                            You don\'t have any orders available. 
                        </div>
                    </div>';
                }

                $response .= '
                    </div>
                </div>
            </div>
        </form>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/update-order-item-status', name: 'clinic_update_order_item_status')]
    public function clinicUpdateOrderItemAction(Request $request): Response
    {
        $data = $request->request;
        $order_id = $data->get('order_id');
        $item_id = $data->get('item_id');
        $link = $data->get('link');
        $order_item = $this->em->getRepository(OrderItems::class)->find($item_id);
        $distributor_id = $order_item->getDistributor()->getId();
        $order_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $order_status = $this->em->getRepository(OrderStatus::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $class = '';

        if($link == 'accept'){

            $order_item->setIsAccepted(1);
            $order_item->setIsRenegotiate(0);
            $order_item->setIsCancelled(0);

            $class = 'bg-success';
        }

        if($link == 'renegotiate'){

            $order_item->setIsAccepted(0);
            $order_item->setIsRenegotiate(1);
            $order_item->setIsCancelled(0);

            $class = 'bg-warning text-light';
        }

        if($link == 'cancelled'){

            $order_item->setIsAccepted(0);
            $order_item->setIsRenegotiate(0);
            $order_item->setIsCancelled(1);

            $class = 'bg-danger text-light';
        }

        $this->em->persist($order_item);

        // Order Status
        $accepted = 0;
        $negotiating = 0;
        $cancelled = 0;
        $action_required = false;

        foreach($order_items as $item){

            $accepted += $item->getIsAccepted();
            $negotiating += $item->getIsRenegotiate();
            $cancelled += $item->getIsCancelled();

            if($item->getIsAccepted() == 0 && $item->getIsRenegotiate() == 0 && $item->getIsCancelled() == 0) {

                $action_required = true;
                $status = 'Pending';

                break;
            }
        }

        if($accepted == 0 && $negotiating == 0 && $cancelled == 0 && !$action_required) {

            $status = 'Pending';

        } elseif($negotiating > 0 && !$action_required){

            $status = 'Renegotiating';

        } elseif($accepted > 0 && $negotiating == 0 && $cancelled >= 0 && !$action_required){

            $status = 'Awaiting Shipping';

        } elseif($accepted == 0 && $negotiating == 0 && $cancelled > 0 && !$action_required){

            $status = 'Cancelled';

        }

        $order_status->setStatus($status);

        $this->em->persist($order_status);
        $this->em->flush();

        $btn = $this->btnConfirmOrder($order_items, $order_id);

        $response = [
            'class' => $class,
            'btn' => $btn
        ];
        $this->generatePpPdfAction($order_id, $distributor_id);
        return new JsonResponse($response);
    }

    #[Route('/distributors/update-order-item-status', name: 'distributor_update_order_item_status')]
    public function distributorUpdateOrderItemAction(Request $request): Response
    {
        $order_item = $this->em->getRepository(OrderItems::class)->find($request->request->get('item_id'));

        $order_item->setIsConfirmedDistributor($request->request->get('confirmed_status'));

        $this->em->persist($order_item);
        $this->em->flush();

        $flash = '<b><i class="fas fa-check-circle"></i> Item status updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'flash' => $flash,
            'distributor_id' => $order_item->getDistributor()->getId(),
            'order_id' => $order_item->getOrders()->getId(),
            'clinic_id' => $order_item->getOrders()->getClinic()->getId()
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/get-order-last-updated', name: 'get_order_last_update')]
    public function getOrderLastUpdatedAction(Request $request): Response
    {
        $data = $request->request;
        $order_id = $data->get('order_id');
        $order = $this->em->getRepository(OrderItems::class)->findOneBy([
            'orders' => $order_id
        ],
        [
            'modified' => 'DESC'
        ]);

        $response = $order->getModified()->format('Y-n-d H:i:s');

        return new JsonResponse($response);
    }

    #[Route('/clinics/confirm_order', name: 'clinic_confirm_order')]
    public function clinicsConfirmOrderAction(Request $request): Response
    {
        $data = $request->request;
        $order_id = $data->get('order_id');
        $order = $this->em->getRepository(OrderItems::class)->findOneBy([
            'orders' => $order_id
        ],
            [
                'modified' => 'DESC'
            ]);

        $response = $order->getModified()->format('Y-n-d H:i:s');

        return new JsonResponse($response);
    }

    private function sendOrderEmail($order_id, $distributor_id, $clinic_id, $type)
    {
        $i = 0;
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $clinic = $this->em->getRepository(Clinics::class)->find($clinic_id);
        $order_items = $this->em->getRepository(OrderItems::class)->findByNotCancelled($order_id,$distributor_id);
        $distributor_name = $distributor->getDistributorName();
        $email_address = $clinic->getEmail();
        $po_number = $distributor->getPoNumberPrefix() .'-'. $order_id;
        $subject = 'Fluid Order - PO '. $po_number;
        $url = $this->getParameter('app.base_url').'/'. $type .'/order/'. $order_id;

        $rows = '
            <table style="border: none; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px; width: 100%">
                <tr>
                    <th style="border: solid 1px #ccc; background: #ccc">#</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Name</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Price</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Qty</th>
                    <th style="border: solid 1px #ccc; background: #ccc">Total</th>
                </tr>';

        foreach($order_items as $item){

            $i++;

            $rows .= '
                <tr>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $i .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getName() .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getUnitPrice() .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getQuantity() .'</td>
                    <td style="border: solid 1px #ccc; border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px">'. $item->getTotal() .'</td>
                </tr>';
        }

        $rows .= '</table>';

        $body = '
            <table style="border-collapse: collapse; padding: 8px; font-family: Arial; font-size: 14px; width: 700px;">
                <tr>
                    <td colspan="2">
                        Please <a href="'. $url .'">click here</a> in order to login in to your Fluid account to manage this order
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td>
                        '. $distributor_name .'
                    </td>
                    <td align="right" rowspan="2">
                        <span style="font-size: 24px">
                            PO Number: '. $po_number .'
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="right">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        '. $rows .'
                    </td>
                </tr>
            </table>';

        $email = (new Email())
            ->from($this->getParameter('app.email_from'))
            ->addTo($email_address)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }

    private function btnConfirmOrder($orders, $order_id)
    {
        $total_items = 0;
        $accepted = 0;
        $cancelled = 0;

        if($orders != null && count($orders) > 0){

            $total_items = count($orders);

            foreach($orders as $order){

                $accepted += $order->getIsAccepted();
                $cancelled += $order->getIsCancelled();
            }
        }

        if(($accepted + $cancelled) == $total_items && $total_items != 0){

            if($cancelled == $total_items){

                $btn_confirm = '
                <a 
                    href="#" 
                    id="btn_cancel_order" 
                    data-order-id="' . $order_id . '"
                    data-clinic-id="' . $orders[0]->getOrders()->getClinic()->getId() . '"
                >
                    <i class="fa-regular fa-credit-card me-5 me-md-2"></i>
                    <span class=" d-none d-md-inline-block pe-4">Cancel & Close Order</span>
                </a>';

            } else {

                $btn_confirm = '
                <a 
                    href="#" 
                    id="btn_confirm_order" 
                    data-order-id="' . $order_id . '"
                    data-clinic-id="' . $orders[0]->getOrders()->getClinic()->getId() . '"
                >
                    <i class="fa-regular fa-credit-card me-5 me-md-2"></i>
                    <span class=" d-none d-md-inline-block pe-4">Confirm Order</span>
                </a>';
            }

        } else {

            $btn_confirm = '
            <span 
                class="disabled"
                id="btn_confirm_order"
            >
                <i class="fa-regular fa-credit-card me-5 me-md-2"></i>
                <span class=" d-none d-md-inline-block pe-4">Confirm Order</span>
            </span>';
        }

        return $btn_confirm;
    }

    public function generatePpPdfAction($order_id, $distributor_id)
    {
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $order_items = $this->em->getRepository(OrderItems::class)->findByDistributorOrder($order_id, $distributor_id);
        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $billing_address = $this->em->getRepository(Addresses::class)->find($order->getBillingAddress());
        $shipping_address = $this->em->getRepository(Addresses::class)->find($order->getAddress());
        $order_status = $this->em->getRepository(OrderStatus::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $additional_notes = '';

        if($order->getNotes() != null){

            $additional_notes = '
            <div style="padding-top: 20px; padding-right: 30px; line-height: 30px">
                <b>Additional Notes</b><br>
                '. $order->getNotes() .'
            </div>';
        }

        $snappy = new Pdf(__DIR__ .'/../../vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64');
        $html = '
        <table style="width: 100%; border: none; border-collapse: collapse; font-size: 12px">
            <tr>
                <td style=" line-height: 25px">
                    <img 
                        src="'. __DIR__ .'/../../public/images/logos/'. $distributor->getLogo() .'"
                        style="width:100%; max-width: 200px"
                    >
                    <br>
                    '. $distributor->getDistributorName() .'<br>
                    '. $distributor->getTelephone() .'<br>
                    '. $distributor->getEmail() .'
                </td>
                <td style="text-align: right">
                    <h1>PURCHASE ORDER</h1>
                    <table style="width: auto;margin-right: 0px;margin-left: auto; text-align: right;font-size: 12px">
                        <tr>
                            <td>
                                DATE:
                            </td>
                            <td style="padding-left: 20px; line-height: 25px">
                                '. date('Y-m-d') .'
                            </td>
                        </tr>
                        <tr>
                            <td>
                                PO#:
                            </td>
                            <td style="line-height: 25px">
                                '. $order_items[0]->getPoNumber() .'
                            </td>
                        </tr>  
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    &nbsp;
                </td>
            </tr>
            <tr>
                <td style="width: 50%; vertical-align: top">
                    <table style="width: 80%; border-collapse: collapse;font-size: 12px">
                        <tr style="background: #7796a8; color: #fff; border: solid 1px #7796a8;">
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                Vendor
                            </th>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px; line-height: 25px">
                                '. $billing_address->getClinicName() .'<br>
                                '. $billing_address->getAddress() .'<br>
                                '. $billing_address->getPostalCode() .'<br>
                                '. $billing_address->getCity() .'<br>
                                '. $billing_address->getState() .'<br>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top">
                    <table style="width: 80%; border-collapse: collapse; margin-left: auto;margin-right: 0; font-size: 12px">
                        <tr style="background: #7796a8; color: #fff">
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                Deliver To
                            </th>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px; line-height: 25px">
                                '. $shipping_address->getClinicName() .'<br>
                                '. $shipping_address->getAddress() .'<br>
                                '. $shipping_address->getPostalCode() .'<br>
                                '. $shipping_address->getCity() .'<br>
                                '. $shipping_address->getState() .'<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    &nbsp;
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px">
                        <tr style="background: #7796a8; color: #fff">
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                #SKU
                            </th>
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                Description
                            </th>
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                Qty
                            </th>
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                Unit Priice
                            </th>
                            <th style="padding: 8px; border: solid 1px #7796a8;">
                                Total
                            </th>
                        </tr>';

                    foreach($order_items as $item) {

                        $name = $item->getName() .': ';
                        $dosage = $item->getProduct()->getDosage() . $item->getProduct()->getUnit() .', '. $item->getProduct()->getSize() .' Count';

                        if($item->getProduct()->getForm() == 'Each'){

                            $dosage = $item->getProduct()->getSize() . $item->getProduct()->getUnit();
                        }

                        $html .= '
                        <tr>
                            <td style="padding: 8px; border: solid 1px #7796a8;text-align: center">
                                '. $item->getProduct()->getDistributorProducts()[0]->getSku() .'
                            </td>
                            <td style="padding: 8px; border: solid 1px #7796a8;">
                                '. $name . $dosage .'
                            </td>
                            <td style="padding: 8px; border: solid 1px #7796a8;text-align: center">
                                '. $item->getQuantity() .'
                            </td>
                            <td style="padding: 8px; border: solid 1px #7796a8;text-align: right; padding-right: 8px; width: 10%">
                                $'. number_format($item->getUnitPrice(),2) .'
                            </td>
                            <td style="padding: 8px; border: solid 1px #7796a8;text-align: right; padding-right: 8px; width: 10%">
                                $'. number_format($item->getTotal(),2) .'
                            </td>
                        </tr>';
                    }

                    $html .= '
                        <tr>
                            <td colspan="3" rowspan="4" style="padding: 8px; padding-top: 16px; border: none;">
                                '. $additional_notes .'
                            </td>
                            <td style="padding: 8px; padding-top: 16px; border: none;text-align: right">
                                Subtotal
                            </td>
                            <td style="padding: 8px; padding-top: 16px;text-align: right; border: none">
                                $'. number_format($order->getSubTotal(),2) .'
                            </td>
                        </tr>';

                        if($order->getDeliveryFee() > 0) {

                            $html .= '
                            <tr>
                                <td style="padding: 8px; border: none;text-align: right">
                                    Delivery
                                </td>
                                <td style="padding: 8px;text-align: right; border: none">
                                    $' . number_format($order->getDeliveryFee(), 2) . '
                                </td>
                            </tr>';
                        }

                        if($order->getTax() > 0) {

                            $html .= '
                            <tr>
                                <td style="padding: 8px; border: none;text-align: right">
                                    Tax
                                </td>
                                <td style="padding: 8px; border:none; text-align: right">
                                    $' . number_format($order->getTax(), 2) . '
                                </td>
                            </tr>';
                        }

                        $html .= '
                        <tr>
                            <td style="padding: 8px; border: none;text-align: right">
                                <b>Total</b>
                            </td>
                            <td style="padding: 8px;text-align: right; border: none">
                                <b>$'. number_format($order->getTotal(),2) .'</b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';

        $file = uniqid() .'.pdf';
        $snappy->generateFromHtml($html,'pdf/'. $file,['page-size' => 'A4']);

        $order_status->setPoFile($file);

        $this->em->persist($order_status);
        $this->em->flush();
    }
}
