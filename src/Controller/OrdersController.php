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
use App\Entity\Status;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class OrdersController extends AbstractController
{
    private $em;
    private $mailer;
    private $page_manager;
    const ITEMS_PER_PAGE = 1;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer, PaginationManager $pagination)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->page_manager = $pagination;
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
            'type' => 2
        ]);

        $default_billing_address = $this->em->getRepository(Addresses::class)->findOneBy([
            'clinic' => $clinic->getId(),
            'isDefaultBilling' => 1,
            'type' => 1
        ]);

        if($default_address != null) {

            $response['default_address_id'] = $default_address->getId();

        } else {

            $response['default_address_id'] = '';
        }

        if($default_billing_address != null) {

            $response['default_billing_address_id'] = $default_billing_address->getId();

        } else {

            $response['default_billing_address_id'] = '';
        }

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
                $order_items->setQuantityDelivered($basket_item->getQty());
                $order_items->setTotal($basket_item->getTotal());
                $order_items->setName($basket_item->getName());
                $order_items->setPoNumber($prefix .'-'. $order->getId());
                $order_items->setOrderPlacedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());
                $order_items->setIsAccepted(0);
                $order_items->setIsRenegotiate(0);
                $order_items->setIsCancelled(0);
                $order_items->setIsConfirmedDistributor(0);
                $order_items->setIsQuantityCorrect(0);
                $order_items->setIsQuantityInCorrect(0);
                $order_items->setIsQuantityAdjust(0);
                $order_items->setIsAcceptedOnDelivery(1);
                $order_items->setIsRejectedOnDelivery(0);
                $order_items->setStatus('Pending');

                $this->em->persist($order_items);

                // Order Status
                if($distributor_id != $basket_item->getDistributor()->getId()){

                    $distributor_id = $basket_item->getDistributor()->getId();
                    $status = $this->em->getRepository(Status::class)->find(2);

                    $order_status = new OrderStatus();

                    $order_status->setOrders($order);
                    $order_status->setDistributor($basket_item->getDistributor());
                    $order_status->setStatus($status);

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
                    <div class="form-control alert alert-secondary" id="checkout_shipping_address">';

                        if($default_address != null) {

                            $response['body'] .=
                            $default_address->getAddress() . '<br>' .
                            $default_address->getCity() . '<br>' .
                            $default_address->getPostalCode() . '<br>' .
                            $default_address->getState();
                        }

                    $response['body'] .= '
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
                    <div class="form-control alert alert-secondary" rows="4" name="address_billing" id="checkout_billing_address">';

                        if($default_billing_address != null) {

                            $response['body'] .=
                                $default_billing_address->getAddress() . '<br>' .
                                $default_billing_address->getCity() . '<br>' .
                                $default_billing_address->getPostalCode() . '<br>' .
                                $default_billing_address->getState();
                        }

                    $response['body'] .= '
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
            $subject = 'Fluid Order - PO '. $order_items[0]->getPoNumber();
            $distributor_name = $order_items[0]->getDistributor()->getDistributorName();
            $po_number = $order_items[0]->getPoNumber();
            $order_url = $this->getParameter('app.base_url') . '/distributors/order/'. $order_items[0]->getOrders()->getId();
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
                $order_item->setQuantityDelivered($quantities[$i]);
                $order_item->setTotal($prices[$i] * $quantities[$i]);

                $this->em->persist($order_item);
            }

            $this->em->flush();

            $sum_total = $this->em->getRepository(OrderItems::class)->findSumTotalPdfOrderItems($order_id, $distributor->getId());

            $order->setSubTotal($sum_total[0]['totals']);
            $order->setTotal($sum_total[0]['totals'] + $order->getDeliveryFee() + $order->getTax());

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
        $this->clinicSendNotification($order, $distributor, $order->getClinic(), 'Order Update');

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
        $order_status_id = $this->em->getRepository(OrderStatus::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor->getId()
        ]);

        $response = '
        <form name="form_distributor_orders" id="form_distributor_orders" class="row" method="post">
            <input type="hidden" name="order_id" value="'. $orders[0]->getOrders()->getId() .'">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center mt-1 mt-sm-5 pt-3 pb-3" id="order_header">
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
                            class="orders_link"
                            data-distributor-id="'. $distributor->getId() .'"
                        >
                            <i class="fa-solid fa-angles-left me-5 me-md-2"></i>
                            <span class=" d-none d-md-inline-block pe-4">Back To Orders</span>
                        </a>
                        <a 
                            href="#" 
                            class="refresh-distributor-order" 
                            data-order-id="'. $order_id .'"
                            data-distributor-id="'. $distributor->getId() .'"
                            data-clinic-id="'. $orders[0]->getOrders()->getClinic()->getId() .'"
                        >
                            <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i>
                            <span class=" d-none d-md-inline-block pe-4">Refresh Order</span>
                        </a>';

                        if($order_status_id->getStatus()->getId() > 4){

                            $response .= '
                            <span class="saved_baskets_link info p-0 opacity-50">
                                <i class="fa-solid fa-floppy-disk me-5  me-md-2"></i>
                                <span class=" d-none d-md-inline-block pe-4">Save Order</span>
                            </span>';

                        } else {

                            $response .= '
                            <button type="submit" class="saved_baskets_link btn btn-sm btn-light p-0 text-primary">
                                <i class="fa-solid fa-floppy-disk me-5  me-md-2"></i>
                                <span class=" d-none d-md-inline-block pe-4">Save Order</span>
                            </button>';

                        }

                        $response .= '
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

                            $status_id = $order_status_id->getStatus()->getId();

                            if($order->getIsCancelled() == 1 && ($status_id == 6 || $status_id == 7 || $status_id == 8)){

                                continue;
                            }

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
                            $badge_shipped = '';
                            $badge_delivered_pending = '';
                            $badge_delivered_correct = '';
                            $badge_delivered_incorrect = '';

                            if($order->getIsCancelled() == 1){

                                $disabled = 'disabled';
                                $opacity = 'opacity-50';

                                $badge_cancelled = '
                                <span
                                    class="badge float-end ms-2 text-light border border-danger text-light order_item_accept bg-danger"
                                >Cancelled</span>';

                            } else {

                                if ($order->getIsConfirmedDistributor() == 1) {

                                    // If order is preparing for shipping or later
                                    if($order->getOrders()->getOrderStatuses()[0]->getStatus()->getId() >= 5){

                                        // Shipped
                                        if($status_id == 6){

                                            $badge_shipped = '
                                            <span 
                                                class="badge float-end ms-2 text-light border border-success bg-success"
                                            >Shipped</span>';
                                        }

                                        // Delivered
                                        if($status_id == 7){

                                            // Quantities not confirmed by clinic
                                            if(
                                                $order->getIsAcceptedOnDelivery() == 0 &&
                                                $order->getIsRejectedOnDelivery() == 0 &&
                                                $order->getIsQuantityAdjust() == 0
                                            ){

                                                $badge_delivered_pending = '
                                                <span 
                                                    class="badge float-end ms-2 text-light border border-dark-grey bg-dark-grey"
                                                >Pending Clinic</span>';
                                            }

                                            // Quantity confirmed by clinic
                                            if($order->getIsAcceptedOnDelivery() == 1){

                                                $badge_delivered_correct = '
                                                <span 
                                                    class="badge float-end ms-2 text-light border border-success bg-success"
                                                >Complete</span>';
                                            }

                                            // Quantity rejected by clinic
                                            if($order->getIsRejectedOnDelivery() == 1){

                                                $badge_delivered_correct = '
                                                <span 
                                                    class="badge float-end ms-2 text-light border border-danger bg-danger"
                                                >Rejected</span>';
                                            }

                                            // Quantity adjust
                                            if($order->getIsQuantityAdjust() == 1){

                                                $badge_delivered_incorrect = '
                                                <span 
                                                    class="badge float-end ms-2 text-light border border-warning bg-warning"
                                                >Adjusting Quantity</span>';
                                            }
                                        }

                                        // Closed
                                        if($status_id == 8){

                                            // Quantity confirmed by clinic
                                            if($order->getIsAcceptedOnDelivery() == 1){

                                                $badge_delivered_correct = '
                                                <span 
                                                    class="badge float-end ms-2 text-light border border-success bg-success"
                                                >Accepted</span>';
                                            }

                                            // Quantity rejected by clinic
                                            if($order->getIsRejectedOnDelivery() == 1){

                                                $badge_delivered_correct = '
                                                <span 
                                                    class="badge float-end ms-2 text-light border border-danger bg-danger"
                                                >Rejected</span>';
                                            }
                                        }

                                    } else {

                                        if ($order->getIsAccepted() == 1) {

                                            $disabled = 'disabled';

                                            $clinic_status = '
                                            <span 
                                                class="badge float-end ms-2 text-light border border-success bg-success"
                                            >Accepted</span>';

                                        } elseif ($order->getIsRenegotiate() == 1) {

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
                                    data-item-id="'. $order->getId() .'"
                                    class="form-control form-control-sm expiry-date ' . $opacity . '" 
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
                                data-item-id="'. $order->getId() .'"
                                value="'. number_format($order->getUnitPrice(),2) .'"
                                class="form-control form-control-sm item-price '. $opacity .'"
                                 '. $disabled .'
                            >';
                            $qty = '
                            <input 
                                type="number" 
                                name="qty[]" 
                                data-item-id="'. $order->getId() .'"
                                class="form-control basket-qty form-control-sm text-center item-qty '. $opacity .'" 
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

                            $popover = '<b>Ordered By</b> '. $order->getOrderPlacedBy() .'<br>';

                            if($order->getOrderReceivedBy() != null){

                                $popover .= '
                                <b>Recieved By</b> '. $order->getOrderReceivedBy();
                            }

                            if($order->getRejectReason() != null){

                                $popover .= '
                                <br><br>
                                <b>Reason For Rejection</b><br>
                                '. $order->getRejectReason();
                            }

                                $response .= '
                                <!-- Product Name and Qty -->
                                '. $prd_id .'
                                <div class="row overflow-hidden">
                                    <!-- Product Name -->
                                    <div class="col-12 col-sm-5 pt-3 pb-3">
                                        <span class="info '. $opacity .'">'. $order->getDistributor()->getDistributorName() .'</span>
                                        <h6 class="fw-bold text-center text-sm-start text-primary lh-base mb-0 '. $opacity .'">
                                            '. $order->getName() .'
                                        </h6>
                                    </div>
                                    <!-- Expiry Date -->
                                    <div class="col-12 col-sm-7 pt-3 pb-3 d-table">
                                        <div class="row d-table-row">
                                            <div class="col-5 text-center text-sm-end d-table-cell align-bottom">
                                                '. $expiry_date .'
                                                <div class="hidden_msg" id="error_expiry_date_'. $order->getProduct()->getId() .'">
                                                    Required Field
                                                </div>
                                            </div>
                                            <div class="col-2 text-center d-table-cell align-bottom">
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
                                            <div class="col-2 text-center text-sm-start fw-bold d-table-cell align-bottom '. $opacity .'">
                                                <button
                                                    type="button"
                                                    class="bg-transparent border-0 text-secondary"
                                                    data-bs-html="true"
                                                    data-bs-trigger="hover"
                                                    data-bs-container="body" 
                                                    data-bs-toggle="popover" 
                                                    data-bs-placement="top" 
                                                    data-bs-content="'. $popover .'"
                                                >
                                                    <i class="fa-solid fa-circle-info"></i>
                                                </button>
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

                                        $response .= $badge_pending . $badge_cancelled . $clinic_status . $badge_shipped .
                                        $badge_delivered_pending . $badge_delivered_correct . $badge_delivered_incorrect;

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
        $results = $this->page_manager->paginate($orders[0], $request, self::ITEMS_PER_PAGE);

        $html = '
        <form name="form_distributor_orders" class="row" id="form_distributor_orders" method="post">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center mt-1 mt-sm-5 pt-3 pb-3" id="order_header">
                        <h4 class="text-white text-truncate">Manage Fluid Orders</h4>
                        <span class="text-white d-none d-sm-inline">
                            Manage All Your Orders In One Place
                        </span>
                    </div>
                </div>';

                if(count($orders[1]) > 0) {

                    $html .= '
                    <!-- Actions Row -->
                    <div class="row" id="order_action_row_1">
                        <div class="col-12 d-flex justify-content-center border-bottom pt-3 pb-3 bg-light border-left border-right">
                            
                        </div>
                    </div>
                    <!-- Orders -->
                    <div class="row d-none d-xl-block">
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

                $html .= '
                <div class="row">
                    <div class="col-12 border-right bg-light col-cell border-left border-right border-bottom">';

                    if(count($orders[1]) > 0) {

                        foreach ($results as $order) {

                            $html .= '
                            <!-- Orders -->
                            <div class="row">
                                <div class="col-4 col-sm-2 d-block d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">#Id: </div>
                                <div class="col-8 col-sm-10 col-xl-1 pt-3 pb-3 border-list text-truncate">
                                    ' . $order->getId() . '
                                </div>
                                <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Clnic: </div>
                                <div class="col-8 col-sm-10 col-xl-4 pt-3 pb-3 text-truncate border-list">
                                    ' . $order->getClinic()->getClinicName() . '
                                </div>
                                <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Total: </div>
                                <div class="col-8 col-sm-10 col-xl-2 pt-3 pb-3 border-list">
                                    $' . number_format($order->getTotal(),2) . '
                                </div>
                                <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Date: </div>
                                <div class="col-8 col-sm-10 col-xl-2 pt-3 pb-3 border-list">
                                    ' . $order->getCreated()->format('Y-m-d') . '
                                </div>
                                <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Status: </div>
                                <div class="col-8 col-sm-10 col-xl-2 pt-3 pb-3 border-list">
                                    ' . ucfirst($order->getOrderStatuses()[0]->getStatus()->getStatus()) . '
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

                        $html .= '
                        <div class="row">
                            <div class="col-12 text-center mt-5 mb-5 pt-3 pb-3 text-center">
                                You don\'t have any orders available. 
                            </div>
                        </div>';
                    }

                    $html .= '
                    </div>
                </div>
            </div>
        </form>';

        $current_page = $request->request->get('page_id');
        $last_page = $this->page_manager->lastPage($results);

        $pageination = '
        <!-- Pagination -->
        <div class="row mt-3">
            <div class="col-12">';

        if($last_page > 1) {

            $previous_page_no = $current_page - 1;
            $url = '/clinics/orders/'. $request->request->get('clinic_id');
            $previous_page = $url . $previous_page_no;

            $pageination .= '
            <nav class="custom-pagination">
                <ul class="pagination justify-content-center">
            ';

            $disabled = 'disabled';
            $data_disabled = 'true';

            // Previous Link
            if($current_page > 1){

                $disabled = '';
                $data_disabled = 'false';
            }

            $pageination .= '
            <li class="page-item '. $disabled .'">
                <a class="order-link" aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page - 1 .'" href="'. $previous_page .'">
                    <span aria-hidden="true">&laquo;</span> <span class="d-none d-sm-inline">Previous</span>
                </a>
            </li>';

            for($i = 1; $i <= $last_page; $i++) {

                $active = '';

                if($i == (int) $current_page){

                    $active = 'active';
                }

                $pageination .= '
                <li class="page-item '. $active .'">
                    <a class="order-link" data-page-id="'. $i .'" href="'. $url .'">'. $i .'</a>
                </li>';
            }

            $disabled = 'disabled';
            $data_disabled = 'true';

            if($current_page < $last_page) {

                $disabled = '';
                $data_disabled = 'false';
            }

            $pageination .= '
            <li class="page-item '. $disabled .'">
                <a class="order-link" aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page + 1 .'" href="'. $url . $current_page + 1 .'">
                    <span class="d-none d-sm-inline">Next</span> <span aria-hidden="true">&raquo;</span>
                </a>
            </li>';

            $pageination .= '
                    </ul>
                </nav>
            </div>';
        }

        $response = [
            'html' => $html,
            'pagination' => $pageination
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/order/', name: 'clinic_get_order_details')]
    public function clinicOrderDetailAction(Request $request): Response
    {
        $data = $request->request;

        $order_id = $data->get('order_id');
        $distributor_id = $data->get('distributor_id');

        if($data->get('order_id') == null && $data->get('distributor_id') == null){

            $order_id = $request->get('order_id');
            $distributor_id = $request->get('distributor_id');
        }

        $statuses = $this->em->getRepository(Status::class)->findByIds(['6','7','8']);
        $chat_messages = $this->em->getRepository(ChatMessages::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $orders = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id

        ]);
        $order_status = $this->em->getRepository(OrderStatus::class)->findOneBy([
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
        <form name="form_distributor_orders" class="row" id="form_distributor_orders" method="post">
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
                        class="bg-light border-left border-right col-12 d-flex justify-content-center border-bottom pt-3 pb-3"
                         id="order_action_row"
                    >
                    <a 
                        href="#" 
                        class="orders_link" 
                        data-order-id="' . $order_id . '"
                        data-distributor-id="' . $distributor_id . '"
                        data-clinic-id="' . $orders[0]->getOrders()->getClinic()->getId() . '"
                    >
                        <i class="fa-solid fa-angles-left me-5 me-md-2"></i>
                        <span class=" d-none d-md-inline-block pe-4">Back To Orders</span>
                    </a>';

                        // If order is preparing for shipping or later
                        $order_status_id = $order_status->getStatus()->getId();
                        if($order_status_id < 5 && $order_status_id != 9) {

                            $response .= '
                            <a 
                                href="#" 
                                class="refresh-clinic-order" 
                                data-order-id="' . $order_id . '"
                                data-distributor-id="' . $distributor_id . '"
                                data-clinic-id="' . $orders[0]->getOrders()->getClinic()->getId() . '"
                            >
                                <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i>
                                <span class=" d-none d-md-inline-block pe-4">Refresh Order</span>
                            </a>
                            ' . $this->btnConfirmOrder($orders, $order_id, $distributor_id);

                        } else {

                            if($order_status_id == 6 || $order_status_id == 7) {

                                $status_string = '
                                <select 
                                    data-distributor-id="'. $distributor_id .'" 
                                    data-order-id="'. $order_id .'" 
                                    id="order_status" 
                                    class="status-dropdown"
                                >';

                                foreach ($statuses as $status) {

                                    $selected = '';
                                    $disabled = '';
                                    $option_id = '';
                                    $data_order_id = '';
                                    $data_distributor_id = '';
                                    $is_accepted = 0;
                                    $is_rejected = 0;
                                    $is_quantity_adjust = true;

                                    // Disable Close Option
                                    $can_close = false;
                                    $item_count = count($orders);

                                    foreach($orders as $order){

                                        if($order->getIsQuantityAdjust() == 1){

                                            $is_quantity_adjust = false;
                                        }

                                        if($order->getIsAcceptedOnDelivery() == 1){

                                            $is_accepted += 1;
                                        }

                                        if($order->getIsRejectedOnDelivery() == 1){

                                            $is_rejected += 1;
                                        }
                                    }

                                    if($is_rejected + $is_accepted == $item_count && $is_quantity_adjust = true){

                                        $can_close = true;
                                    }

                                    if($status->getId() == 8 && $order_status_id == 6){

                                        $disabled = 'disabled';
                                    }

                                    if($status->getId() == 8 && $order_status_id == 7){

                                        $option_id = 'id="close_order" ';
                                        $data_order_id = 'data-order-id="'. $order_id .'" ';
                                        $data_distributor_id = 'data-distributor-id="'. $distributor_id .'" ';
                                    }

                                    if($status->getId() == 8 && !$can_close){

                                        $disabled = 'disabled ';
                                    }

                                    if ($status->getId() == $order_status_id) {

                                        $selected = 'selected ';
                                    }

                                    $status_string .= '
                                    <option
                                       
                                        value="' . $status->getId() . '" 
                                        ' . $selected . $disabled . $option_id . $data_order_id . $data_distributor_id .'
                                    >
                                        ' . $status->getStatus() . '
                                    </option>';
                                }

                                $status_string .= '</select>';

                            } else {

                                $status_string = $order_status->getStatus()->getStatus();
                            }

                            $response .= '
                            <span class="text-primary pe-4">
                                <b class="pe-2 d-none d-md-inline-block">Order Status:</b>
                                '. $status_string .'
                            </span>
                            <a 
                                href="'. $this->getParameter('app.base_url') .'/pdf_po.php?pdf='. $order_status->getPoFile() .'"
                                id="btn_download_po"
                                data-pdf="'. $order_status->getPoFile() .'"
                                target="_blank"
                            >
                                <i class="fa-solid fa-file-pdf me-5 me-md-2"></i>
                                <span class="d-none d-md-inline-block pe-4">Download</span>
                            </a>';
                        }

                    $response .= '
                    </div>
                </div>
                <!-- Products -->
                <div class="row border-0 bg-light">
                    <div class="col-12 col-md-9 border-right col-cell border-left border-right border-bottom">
                        <input type="hidden" name="distributor_id" value="'. $distributor_id .'">';

                        foreach($orders as $order) {

                            $expiry = '';
                            $opacity = '';

                            // Don't show cancelled on delivery
                            if($order->getIsCancelled() == 1 && $order_status_id == 7){

                                continue;
                            }

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
                                $opacity = 'opacity-50';

                            } else {

                                $badge_cancelled = 'badge-danger-outline-only';
                            }

                            // Display the qty delivered field if delivered
                            $col_exp_date = 6;
                            $col_qty_delivered = '
                            <div class="col-1 d-table-cell align-bottom text-end alert-text-grey">
                                '. $order->getQuantityDelivered() .'
                            </div>';

                            if($order_status_id == 7){

                                $col_exp_date = 4;

                                if($order->getIsQuantityAdjust() == 1){

                                    $col_qty_delivered = '
                                    <div class="col-2 d-table-cell align-bottom text-end alert-text-grey">
                                        <input 
                                            type="number" 
                                            class="form-control form-control-sm order-qty-delivered" 
                                            value="'. $order->getQuantityDelivered() .'"
                                            data-qty-delivered-id="'. $order->getId() .'"
                                            
                                        >
                                    </div>';
                                }
                            }

                            $response .= '
                            <!-- Product Name and Qty -->
                            <div class="row">
                                <!-- Product Name -->
                                <div class="col-12 col-sm-6 pt-3 pb-3 '. $opacity .'">
                                    <span class="info">'. $order->getDistributor()->getDistributorName() .'</span>
                                    <h6 class="fw-bold text-center text-sm-start text-primary lh-base">
                                        '. $order->getName() .'
                                    </h6>
                                </div>
                                <!-- Expiry Date -->
                                <div class="col-12 col-sm-6 pt-3 pb-3 d-table '. $opacity .'">
                                    <div class="row d-table-row">
                                        <div class="col-'. $col_exp_date .' text-center text-sm-end d-table-cell align-bottom text-end alert-text-grey">
                                            '. $expiry .'
                                        </div>
                                        <div class="col-2 text-center d-table-cell align-bottom text-end alert-text-grey">
                                            $'. number_format($order->getUnitPrice(),2) .'
                                        </div>
                                        <div class="col-1 d-table-cell align-bottom text-end alert-text-grey">
                                            '. $order->getQuantity() .'
                                        </div>';

                                        $response .= $col_qty_delivered;
                                        $popover = '<b>Ordered By</b> '. $order->getOrderPlacedBy() .'<br>';

                                        if($order->getOrderReceivedBy() != null){

                                            $popover .= '
                                            <b>Recieved By</b> '. $order->getOrderReceivedBy();
                                        }

                                        if($order->getRejectReason() != null){

                                            $popover .= '
                                            <br><br>
                                            <b>Reason For Rejection</b><br>
                                            '. $order->getRejectReason();
                                        }

                                        $response .= '
                                        <div class="col-2 text-center text-sm-end fw-bold d-table-cell align-bottom alert-text-grey">
                                            $'. number_format($order->getUnitPrice() * $order->getQuantityDelivered(),2) .'
                                        </div>
                                        <div class="col-2 d-table-cell align-bottom text-end">
                                            <button
                                                type="button"
                                                class="bg-transparent border-0 text-secondary"
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                data-bs-container="body" 
                                                data-bs-toggle="popover" 
                                                data-bs-placement="top" 
                                                data-bs-content="'. $popover .'"
                                            >
                                                <i class="fa-solid fa-circle-info"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="col-12 pb-2">';

                                    if($order->getIsConfirmedDistributor() == 1) {

                                        // If order is preparing for shipping or later
                                        if($order->getOrders()->getOrderStatuses()[0]->getStatus()->getId() >= 5){

                                            // Delivered status, check quantity delivered == quantity ordered
                                            if($order_status_id == 7) {

                                                // Accept CTA
                                                if ($order->getIsAcceptedOnDelivery() == 1) {

                                                    $btn_accept = '
                                                    <a href="#" 
                                                        class="badge float-end ms-2 text-light border-success bg-success btn-item-accept"
                                                        data-order-id="' . $order_id . '"
                                                        data-item-id="' . $order->getId() . '"
                                                    >Accept</a>';

                                                } else {

                                                    $btn_accept = '
                                                    <a href="#" 
                                                        class="badge float-end ms-2 text-success border-success badge-success-outline-only btn-item-accept"
                                                        data-order-id="' . $order_id . '"
                                                        data-item-id="' . $order->getId() . '"
                                                    >Accept</a>';
                                                }

                                                // Reject CTA
                                                if ($order->getIsRejectedOnDelivery() == 1) {

                                                    $btn_reject = '
                                                    <a href="#" 
                                                        class="badge float-end ms-2 text-light border-danger bg-danger btn-item-reject"
                                                        data-order-id="' . $order_id . '"
                                                        data-item-id="' . $order->getId() . '"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modal_reject_item"
                                                    >Reject</a>';

                                                } else {

                                                    $btn_reject = '
                                                    <a href="#" 
                                                        class="badge float-end ms-2 text-danger border-danger badge-danger-outline-only btn-item-reject"
                                                        data-order-id="' . $order_id . '"
                                                        data-item-id="' . $order->getId() . '"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modal_reject_item"
                                                    >Reject</a>';
                                                }

                                                // Qty CTA
                                                if ($order->getIsQuantityAdjust() == 1) {

                                                    $btn_qty = '
                                                    <a href="#" 
                                                        class="badge float-end ms-2 text-light border-warning bg-warning btn-item-qty"
                                                        data-order-id="' . $order_id . '"
                                                        data-item-id="' . $order->getId() . '"
                                                    >Adjust Quantity</a>';

                                                } else {

                                                    $btn_qty = '
                                                    <a href="#" 
                                                        class="badge float-end ms-2 text-warning border-warning badge-warning-outline-only btn-item-qty"
                                                        data-order-id="' . $order_id . '"
                                                        data-item-id="' . $order->getId() . '"
                                                    >Adjust Quantity</a>';
                                                }

                                                $response .= $btn_accept . $btn_qty . $btn_reject;

                                            } elseif($order_status_id == 8){

                                                $btn_status = '';

                                                // Accept
                                                if ($order->getIsAcceptedOnDelivery() == 1) {

                                                    $btn_status = '
                                                    <span 
                                                        class="badge float-end ms-2 text-light border-success bg-success btn-item-accept"
                                                    >Accepted</span>';

                                                }

                                                // Reject
                                                if ($order->getIsRejectedOnDelivery() == 1) {

                                                    $btn_status = '
                                                    <span 
                                                        class="badge float-end ms-2 text-light border-danger bg-danger btn-item-reject"
                                                    >Rejected</span>';
                                                }

                                                $response .= $btn_status;

                                            } else {

                                                if ($order->getIsAccepted() == 1) {

                                                    $response .= '
                                                    <span 
                                                        class="badge float-end ms-2 text-success border border-success text-light bg-success"
                                                    >Accepted</span>';
                                                }

                                                if ($order->getIsCancelled() == 1) {

                                                    $response .= '
                                                    <span 
                                                        class="badge float-end ms-2 text-success border border-danger text-light bg-danger"
                                                    >Cancelled</span>';
                                                }
                                            }

                                        // Accept, Renegotiate and Cancel
                                        } else {

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
                                        }

                                    // Pending Distributor
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
        </form>
        
        <!-- Reject Item modal -->
        <div class="modal fade" id="modal_reject_item" tabindex="-1" aria-labelledby="modal_reject_item" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form name="form_reject_item" method="post">
                        <input type="hidden" name="reject_item_id" id="reject_item_id">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <button type="button" class="btn-close float-end me-2 position-absolute end-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                <!-- Reject -->
                                <div class="col-12">
                                    <label class="pt-4">Reason For Rejection*</label>
                                    <textarea 
                                        id="reject_reason"
                                        type="text" 
                                        name="reject_reason"
                                        class="form-control"
                                    ></textarea>
                                    <div class="hidden_msg" id="error_reject_reason">
                                        Required Field
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                            <button 
                                type="submit" 
                                class="btn btn-primary" 
                                data-item-id="'. $order->getId() .'"
                            >SAVE</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/orders', name: 'clinic_get_order_list')]
    public function clinicGetOrdersAction(Request $request): Response
    {
        $clinic = $this->getUser()->getClinic();
        $orders = $this->em->getRepository(Orders::class)->findClinicOrders($clinic->getId());
        $results = $this->page_manager->paginate($orders[0], $request, self::ITEMS_PER_PAGE);

        $html = '
        <div class="col-12">
            <div class="row">
                <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3" id="order_header">
                    <h4 class="text-white text-truncate">Manage Fluid Orders</h4>
                    <span class="text-white d-none d-sm-inline">
                        Manage All Your Orders In One Place
                    </span>
                </div>
            </div>';

            if(count($orders) > 0) {

                $html .= '
                <!-- Orders -->
                <div class="row d-none d-xl-block">
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

                foreach ($results as $order) {

                    $html .= '
                    <!-- Orders -->
                    <div class="row">
                        <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">#Id </div>
                        <div class="col-8 col-md-10 col-xl-1 t-cell text-truncate border-list pt-3 pb-3">
                            ' . $order->getId() . '
                        </div>
                        <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Distributor </div>
                        <div class="col-8 col-md-10 col-xl-4 t-cell text-truncate border-list pt-3 pb-3">
                            ' . $order->getOrderItems()[0]->getDistributor()->getDistributorName() . '
                        </div>
                        <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Total </div>
                        <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                            $' . number_format($order->getTotal(),2) . '
                        </div>
                        <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Daste </div>
                        <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                            ' . $order->getCreated()->format('Y-m-d') . '
                        </div>
                        <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Status </div>
                        <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                            ' . ucfirst($order->getOrderStatuses()[0]->getStatus()->getStatus()) . '
                        </div>
                        <div class="col-12 col-sm-1 pt-3 pb-3 text-end border-list">
                            <a 
                                href="' . $this->getParameter('app.base_url') . '/clinics/order/' . $order->getId() . '/' . $order->getOrderStatuses()[0]->getDistributor()->getId() . '" 
                                class="pe-0 pe-sm-3 float-end"
                                id="order_detail_link"
                                data-order-id="' . $order->getId() . '"
                                data-distributor-id="' . $order->getOrderStatuses()[0]->getDistributor()->getId() . '"
                                data-clinic-id="' . $order->getClinic()->getId() . '"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </div>
                    </div>';
                }

            } else {

                $html .= '
                <div class="row">
                    <div class="col-12 text-center mt-5 mb-5 pt-3 pb-3 text-center">
                        You don\'t have any orders available. 
                    </div>
                </div>';
            }

            $html .= '
                </div>
            </div>
        </div>';

        $current_page = $request->request->get('page_id');
        $last_page = $this->page_manager->lastPage($results);

        $pageination = '
        <!-- Pagination -->
        <div class="row">
            <div class="col-12">';

        if($last_page > 1) {

            $previous_page_no = $current_page - 1;
            $url = '/clinics/orders/'. $request->request->get('clinic_id');
            $previous_page = $url . $previous_page_no;

            $pageination .= '
            <nav class="custom-pagination">
                <ul class="pagination justify-content-center">
            ';

            $disabled = 'disabled';
            $data_disabled = 'true';

            // Previous Link
            if($current_page > 1){

                $disabled = '';
                $data_disabled = 'false';
            }

            $pageination .= '
            <li class="page-item '. $disabled .'">
                <a class="order-link" aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page - 1 .'" href="'. $previous_page .'">
                    <span aria-hidden="true">&laquo;</span> <span class="d-none d-sm-inline-block">Previous</span>
                </a>
            </li>';

            for($i = 1; $i <= $last_page; $i++) {

                $active = '';

                if($i == (int) $current_page){

                    $active = 'active';
                }

                $pageination .= '
                <li class="page-item '. $active .'">
                    <a class="order-link" data-page-id="'. $i .'" href="'. $url .'">'. $i .'</a>
                </li>';
            }

            $disabled = 'disabled';
            $data_disabled = 'true';

            if($current_page < $last_page) {

                $disabled = '';
                $data_disabled = 'false';
            }

            $pageination .= '
            <li class="page-item '. $disabled .'">
                <a class="order-link" aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page + 1 .'" href="'. $url . $current_page + 1 .'">
                    <span class="d-none d-sm-inline-block">Next</span> <span aria-hidden="true">&raquo;</span>
                </a>
            </li>';

            $pageination .= '
                    </ul>
                </nav>
            </div>';
        }

        $response = [
            'pagination' => $pageination,
            'html' => $html
        ];

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

            $this->distributorSendNotification($order_id,$distributor_id);
        }

        if($link == 'cancelled'){

            $order_item->setIsAccepted(0);
            $order_item->setIsRenegotiate(0);
            $order_item->setIsCancelled(1);

            $class = 'bg-danger text-light';
        }

        $order_item->setOrderPlacedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($order_item);
        $this->em->flush();

        // Order Status
        $accepted = 0;
        $negotiating = 0;
        $cancelled = 0;
        $status_id = 0;
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

        // Pending
        if($accepted == 0 && $negotiating == 0 && $cancelled == 0) {

            $status_id = 2;

        // Negotiating
        } elseif($negotiating > 0 && !$action_required){

            $status_id = 4;

        // Accepted
        } elseif($accepted > 0 && $negotiating == 0 && $cancelled >= 0 && !$action_required){

            $status_id = 1;

        // Cancelled
        } elseif($accepted == 0 && $negotiating == 0 && $cancelled > 0 && !$action_required){

            $status_id = 8;

        // Pending
        } elseif($action_required){

            $status_id = 2;
        }

        $status = $this->em->getRepository(Status::class)->find($status_id);
        $order_status->setStatus($status);

        $this->em->persist($order_status);
        $this->em->flush();

        $btn = $this->btnConfirmOrder($order_items, $order_id, $distributor_id);

        $response = [
            'class' => $class,
            'btn' => $btn
        ];
        $this->generatePpPdfAction($order_id, $distributor_id, 'Draft');
        return new JsonResponse($response);
    }

    #[Route('/distributors/update-order-item-status', name: 'distributor_update_order_item_status')]
    public function distributorUpdateOrderItemAction(Request $request): Response
    {
        $order_item = $this->em->getRepository(OrderItems::class)->find($request->request->get('item_id'));
        $order_id = $order_item->getOrders()->getId();
        $distributor_id = $order_item->getDistributor()->getId();
        $all_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $total_item_count = count($all_items);
        $confirmed_count = 0;

        $order_item->setIsConfirmedDistributor($request->request->get('confirmed_status'));

        $this->em->persist($order_item);
        $this->em->flush();

        // Send notification to clinic if all items confirmed
        foreach($all_items as $item){

            if($item->getIsConfirmedDistributor() == 1){

                $confirmed_count += 1;
            }
        }

        if($confirmed_count == $total_item_count){

            $this->clinicSendNotification(
                $order_item->getOrders(), $order_item->getDistributor(),
                $order_item->getOrders()->getClinic(), 'Order Update'
            );
        }

        $flash = '<b><i class="fas fa-check-circle"></i> Item status updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'flash' => $flash,
            'distributor_id' => $order_item->getDistributor()->getId(),
            'order_id' => $order_item->getOrders()->getId(),
            'clinic_id' => $order_item->getOrders()->getClinic()->getId()
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/update-expiry-date', name: 'distributor_update_expiry_date')]
    public function distributorUpdateExpiryDateAction(Request $request): Response
    {
        $order_item = $this->em->getRepository(OrderItems::class)->find($request->request->get('item_id'));
        $expiry_date = $request->request->get('expiry_date');

        $order_item->setExpiryDate(\DateTime::createFromFormat('Y-m-d', $expiry_date));

        //$this->clinicSendNotification($order_item->getOrders(), $order_item->getDistributor(), $order_item->getOrders()->getClinic(), 'Order Update');

        $this->em->persist($order_item);
        $this->em->flush();

        $flash = '<b><i class="fas fa-check-circle"></i> Expiry date updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'flash' => $flash,
            'distributor_id' => $order_item->getDistributor()->getId(),
            'order_id' => $order_item->getOrders()->getId(),
            'clinic_id' => $order_item->getOrders()->getClinic()->getId()
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/update-item-price', name: 'distributor_update_price')]
    public function distributorUpdatePriceAction(Request $request): Response
    {
        $order_item = $this->em->getRepository(OrderItems::class)->find($request->request->get('item_id'));
        $order = $order_item->getOrders();
        $price = $request->request->get('price');

        $order_item->setUnitPrice($price);
        $order_item->setTotal($price * $order_item->getQuantity());

        $this->em->persist($order_item);
        $this->em->flush();

        $sum_total = $this->em->getRepository(OrderItems::class)->findSumTotalPdfOrderItems(
            $order_item->getOrders()->getId(),
            $order_item->getDistributor()->getId()
        );

        $order->setSubTotal($sum_total[0]['totals']);
        $order->setTotal($sum_total[0]['totals'] + $order->getDeliveryFee() + $order->getTax());

        $this->em->persist($order);
        $this->em->flush();

        //$this->clinicSendNotification($order, $order_item->getDistributor(), $order_item->getOrders()->getClinic(), 'Order Update');

        $flash = '<b><i class="fas fa-check-circle"></i> Price updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'flash' => $flash,
            'distributor_id' => $order_item->getDistributor()->getId(),
            'order_id' => $order_item->getOrders()->getId(),
            'clinic_id' => $order_item->getOrders()->getClinic()->getId()
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/update-item-qty', name: 'distributor_update_qty')]
    public function distributorUpdateQtyAction(Request $request): Response
    {
        $order_item = $this->em->getRepository(OrderItems::class)->find($request->request->get('item_id'));
        $order = $order_item->getOrders();
        $qty = $request->request->get('qty');

        $order_item->setQuantity($qty);
        $order_item->setQuantityDelivered($qty);
        $order_item->setTotal($qty * $order_item->getUnitPrice());

        $this->em->persist($order_item);
        $this->em->flush();

        $sum_total = $this->em->getRepository(OrderItems::class)->findSumTotalPdfOrderItems(
            $order_item->getOrders()->getId(),
            $order_item->getDistributor()->getId()
        );

        $order->setSubTotal($sum_total[0]['totals']);
        $order->setTotal($sum_total[0]['totals'] + $order->getDeliveryFee() + $order->getTax());

        $this->em->persist($order);
        $this->em->flush();

        //$this->clinicSendNotification($order, $order_item->getDistributor(), $order_item->getOrders()->getClinic(), 'Order Update');

        $flash = '<b><i class="fas fa-check-circle"></i> Quantity updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

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
    public function clinicsConfirmOrderAction(Request $request, MailerInterface $mailer): Response{
        $data = $request->request;
        $order_id = $data->get('order_id');
        $clinic_id = $data->get('clinic_id');
        $clinic = $this->em->getRepository(Clinics::class)->find($clinic_id);
        $distributor_id = $data->get('distributor_id');
        $order = $this->em->getRepository(OrderItems::class)->findByDistributorOrder($order_id, $distributor_id, 'Draft');
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $status = $this->em->getRepository(Status::class)->find(5);
        $order_status = $this->em->getRepository(OrderStatus::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);

        // Generate PO
        $file = $this->generatePpPdfAction($order_id, $distributor_id, 'Draft');

        $clinic_html = '
        Your order with '. $distributor->getDistributorName() .' has been accepted and  will be dispatched within 24 hours.
        <br>
        <br>
        <a href="'. $this->getParameter('app.base_url') .'/clinics/order/'. $order_id .'/'. $distributor_id .'">
            View Order
        </a>
        ';

        $distributor_html = '
        Your order for '. $clinic->getClinicName() .' has been accepted.
        <br>
        <br>
        <a href="'. $this->getParameter('app.base_url') .'/distributors/order/'. $order_id .'">
            View Order
        </a>
        ';

        // Distributor Email
        $email = (new Email())
            ->from($this->getParameter('app.email_from'))
            ->addTo($distributor->getEmail())
            ->attachFromPath(__DIR__ . '/../../public/pdf/' . $file)
            ->subject('Fluid Order - PO  '. $order[0]->getPoNumber())
            ->html($distributor_html);

        $mailer->send($email);

        // Clinic Email
        $email = (new Email())
            ->from($this->getParameter('app.email_from'))
            ->addTo($clinic->getEmail())
            ->attachFromPath(__DIR__ . '/../../public/pdf/' . $file)
            ->subject('Fluid Order - PO  '. $order[0]->getPoNumber())
            ->html($clinic_html);

        $mailer->send($email);

        // Update Status
        $order_status->setStatus($status);

        $this->em->persist($order_status);
        $this->em->flush();

        $orders = $this->forward('App\Controller\OrdersController::clinicGetOrdersAction')->getContent();
        $flash = '<b><i class="fas fa-check-circle"></i> Purchase order successfully sent.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'orders' => json_decode($orders),
            'flash' => $flash
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/update-order-status', name: 'clinic_update_order_status')]
    public function clinicsUpdateOrderStatusAction(Request $request): Response{

        $data = $request->request;
        $status_id = (int) $data->get('order_status');
        $distributor_id = (int) $data->get('distributor_id');
        $order_id = (int) $data->get('order_id');
        $order_status = $this->em->getRepository(OrderStatus::class)->findOneBy([
            'distributor' => $distributor_id,
            'orders' => $order_id
        ]);
        $status = $this->em->getRepository(Status::class)->find($status_id);

        $order_status->setStatus($status);

        $this->em->persist($order_status);
        $this->em->flush();

        $response = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
            'distributor_id' => $distributor_id,
            'order_id' => $order_id
        ])->getContent();

        return new JsonResponse(json_decode(($response)));
    }

    #[Route('/clinics/update-qty-delivered', name: 'clinic_update_qty_delivered')]
    public function clinicsUpdateQtyDeliveredAction(Request $request): Response{

        $data = $request->request;
        $order_item_id = $data->get('item_id');
        $qty_delivered = $data->get('qty');
        $distributor_id = $data->get('distributor_id');
        $order_id = $data->get('order_id');
        $order_item = $this->em->getRepository(OrderItems::class)->find($order_item_id);
        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $stock_count = $this->em->getRepository(DistributorProducts::class)->findByDistributorProductStockCount(
            $order_item->getProduct()->getId(),
            $order_item->getDistributor()->getId()
        );

        if($stock_count[0]['stock_count'] < $qty_delivered){

            $orders = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
                'distributor_id' => $distributor_id,
                'order_id' => $order_id
            ])->getContent();

            $response['orders'] = json_decode($orders);
            $response['flash'] = '<b><i class="fa solid fa-circle-xmark"></i> Only '. $stock_count[0]['stock_count'] .' available .<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($response);
        }

        $order_item->setQuantityDelivered($qty_delivered);
        $order_item->setTotal($qty_delivered * $order_item->getUnitPrice());

        $this->em->persist($order_item);
        $this->em->flush();

        $sum_total = $this->em->getRepository(OrderItems::class)->findSumTotalPdfOrderItems(
            $order_item->getOrders()->getId(),
            $order_item->getDistributor()->getId()
        );

        $order->setSubTotal($sum_total[0]['totals']);
        $order->setTotal($sum_total[0]['totals'] + $order->getDeliveryFee() + $order->getTax());

        $this->em->persist($order);
        $this->em->flush();

        $orders = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
            'distributor_id' => $distributor_id,
            'order_id' => $order_id
        ])->getContent();

        $this->distributorSendNotification($order_id, $distributor_id);

        $response['orders'] = json_decode($orders);
        $response['flash'] = '<b><i class="fas fa-check-circle"></i> Quantity delivered updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/is-delivered-quantity-correct', name: 'is_delivered_quantity_correct')]
    public function clinicsIsDeliveredQtyCorrectAction(Request $request): Response{

        $data = $request->request;
        $order_item_id = $data->get('item_id');
        $distributor_id = $data->get('distributor_id');
        $order_id = $data->get('order_id');
        $qty_is_correct = $data->get('is_correct');
        $qty_is_incorrect = $data->get('is_incorrect');
        $order_item = $this->em->getRepository(OrderItems::class)->find($order_item_id);

        if($qty_is_correct){

            $order_item->setIsQuantityCorrect(1);
            $order_item->setIsQuantityIncorrect(0);

        } elseif($qty_is_incorrect){

            $order_item->setIsQuantityIncorrect(1);
            $order_item->setIsQuantityCorrect(0);

        } else {

            $order_item->setIsQuantityIncorrect(0);
            $order_item->setIsQuantityCorrect(0);
        }

        $this->em->persist($order_item);
        $this->em->flush();

        $orders = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
            'distributor_id' => $distributor_id,
            'order_id' => $order_id
        ])->getContent();

        $response['orders'] = json_decode($orders);
        $response['flash'] = '<b><i class="fas fa-check-circle"></i> Quantity delivered updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/is-delivered-accept', name: 'is_delivered_accept')]
    public function clinicsIsDeliveredAcceptAction(Request $request): Response
    {
        $order_item_id = $request->request->get('item_id');
        $order_item = $this->em->getRepository(OrderItems::class)->find($order_item_id);
        $distributor_id = $request->request->get('distributor_id');
        $order_id = $request->request->get('order_id');

        if($order_item->getIsAcceptedOnDelivery() == 1){

            $is_accepted = 0;

        } else {

            $is_accepted = 1;
        }

        $order_item->setIsAcceptedOnDelivery($is_accepted);
        $order_item->setIsRejectedOnDelivery(0);
        $order_item->setIsQuantityAdjust(0);
        $order_item->setRejectReason('');
        $order_item->setOrderReceivedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($order_item);
        $this->em->flush();

        $order_count = $order_item->getOrders()->getOrderItems()->count();
        $accepted_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'isAcceptedOnDelivery' => 1
        ]);
        $rejected_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'isRejectedOnDelivery' => 1
        ]);

        if(count($accepted_items) + count($rejected_items) == $order_count){

            $this->generatePpPdfAction($order_id, $distributor_id, 'Confirmed');
        }

        $orders = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
            'distributor_id' => $distributor_id,
            'order_id' => $order_id
        ])->getContent();

        $response['orders'] = json_decode($orders);
        $response['flash'] = '<b><i class="fas fa-check-circle"></i> Quantity delivered updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/is-delivered-qty', name: 'is_delivered_qty')]
    public function clinicsIsDeliveredQtyAction(Request $request): Response
    {
        $order_item_id = $request->request->get('item_id');
        $order_item = $this->em->getRepository(OrderItems::class)->find($order_item_id);
        $distributor_id = $request->request->get('distributor_id');
        $order_id = $request->request->get('order_id');

        if($order_item->getIsQuantityAdjust() == 1){

            $is_adjust = 0;

        } else {

            $is_adjust = 1;
        }

        $order_item->setRejectReason('');
        $order_item->setIsQuantityAdjust($is_adjust);
        $order_item->setIsAcceptedOnDelivery(0);
        $order_item->setIsRejectedOnDelivery(0);
        $order_item->setOrderReceivedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($order_item);
        $this->em->flush();

        $orders = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
            'distributor_id' => $distributor_id,
            'order_id' => $order_id
        ])->getContent();

        $response['orders'] = json_decode($orders);
        $response['flash'] = '<b><i class="fas fa-check-circle"></i> Quantity delivered updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/reject-item', name: 'clinics_reject_item')]
    public function clinicsRejectItemAction(Request $request): Response
    {
        $order_item_id = $request->request->get('reject_item_id');
        $reject_reason = $request->request->get('reject_reason');

        $order_item = $this->em->getRepository(OrderItems::class)->find($order_item_id);

        $order_item->setIsQuantityAdjust(0);
        $order_item->setIsAcceptedOnDelivery(0);
        $order_item->setIsRejectedOnDelivery(1);
        $order_item->setRejectReason($reject_reason);
        $order_item->setOrderReceivedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($order_item);
        $this->em->flush();

        $distributor_id = $order_item->getDistributor()->getId();
        $order_id = $order_item->getOrders()->getId();

        $order_count = $order_item->getOrders()->getOrderItems()->count();
        $accepted_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'isAcceptedOnDelivery' => 1
        ]);
        $rejected_items = $this->em->getRepository(OrderItems::class)->findBy([
            'orders' => $order_id,
            'isRejectedOnDelivery' => 1
        ]);

        if(count($accepted_items) + count($rejected_items) == $order_count){

            $this->generatePpPdfAction($order_id, $distributor_id, 'Confirmed');
        }

        $orders = $this->forward('App\Controller\OrdersController::clinicOrderDetailAction', [
            'distributor_id' => (int) $distributor_id,
            'order_id' => (int) $order_id
        ])->getContent();

        $response['orders'] = json_decode($orders);
        $response['flash'] = '<b><i class="fas fa-check-circle"></i> Item successfully rejected.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/get-reject-reason', name: 'clinics_get_reject_reason')]
    public function clinicsGetRejectReasonAction(Request $request): Response
    {
        $order_item_id = $request->request->get('item_id');

        $order_item = $this->em->getRepository(OrderItems::class)->find($order_item_id);

        if($order_item->getRejectReason() == null){

            $response = '';

        } else {

            $response = $order_item->getRejectReason();
        }

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

    private function btnConfirmOrder($orders, $order_id, $distributor_id)
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
                    data-distributor-id="' . $distributor_id . '"
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
                    data-distributor-id="'. $distributor_id .'"
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

    public function generatePpPdfAction($order_id, $distributor_id, $status)
    {
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $order_items = $this->em->getRepository(OrderItems::class)->findByDistributorOrder(
            (int) $order_id,
            (int) $distributor_id,
            $status
        );

        if(count($order_items) == 0){

            return '';
        }

        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $billing_address = $this->em->getRepository(Addresses::class)->find($order->getBillingAddress());
        $shipping_address = $this->em->getRepository(Addresses::class)->find($order->getAddress());
        $order_status = $this->em->getRepository(OrderStatus::class)->findOneBy([
            'orders' => $order_id,
            'distributor' => $distributor_id
        ]);
        $sum_total = $this->em->getRepository(OrderItems::class)->findSumTotalOrderItems($order_id, $distributor_id);

        $order->setSubTotal($sum_total[0]['totals']);
        $order->setTotal($sum_total[0]['totals'] +  + $order->getDeliveryFee() + $order->getTax());

        $this->em->persist($order);
        $this->em->flush();

        $additional_notes = '';

        if($order->getNotes() != null){

            $additional_notes = '
            <div style="padding-top: 20px; padding-right: 30px; line-height: 30px">
                <b>Additional Notes</b><br>
                '. $order->getNotes() .'
            </div>';
        }

        $address = '';

        if($distributor->getAddressStreet() != null){

            $address .= $distributor->getAddressStreet() .'<br>';
        }

        if($distributor->getAddressCity() != null){

            $address .= $distributor->getAddressCity() .'<br>';
        }

        if($distributor->getAddressPostalCode() != null){

            $address .= $distributor->getAddressPostalCode() .'<br>';
        }

        if($distributor->getAddressState() != null){

            $address .= $distributor->getAddressState() .'<br>';
        }

        if($distributor->getAddressCountry() != null){

            $address .= $distributor->getAddressCountry()->getName() .'<br>';
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
                    '. $address .'
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
                        <tr>
                            <td>
                                Status:
                            </td>
                            <td style="line-height: 25px">
                                '. $status .'
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

                        if ($item->getIsAccepted() == 1) {

                            $name = $item->getName() . ': ';
                            $dosage = $item->getProduct()->getDosage() . $item->getProduct()->getUnit() . ', ' . $item->getProduct()->getSize() . ' Count';

                            if ($item->getProduct()->getForm() == 'Each') {

                                $dosage = $item->getProduct()->getSize() . $item->getProduct()->getUnit();
                            }

                            $html .= '
                            <tr>
                                <td style="padding: 8px; border: solid 1px #7796a8;text-align: center">
                                    ' . $item->getProduct()->getDistributorProducts()[0]->getSku() . '
                                </td>
                                <td style="padding: 8px; border: solid 1px #7796a8;">
                                    ' . $name . $dosage . '
                                </td>
                                <td style="padding: 8px; border: solid 1px #7796a8;text-align: center">
                                    ' . $item->getQuantity() . '
                                </td>
                                <td style="padding: 8px; border: solid 1px #7796a8;text-align: right; padding-right: 8px; width: 10%">
                                    $' . number_format($item->getUnitPrice(), 2) . '
                                </td>
                                <td style="padding: 8px; border: solid 1px #7796a8;text-align: right; padding-right: 8px; width: 10%">
                                    $' . number_format($item->getTotal(), 2) . '
                                </td>
                            </tr>';
                        }
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
dd('xxxx');
        $file = uniqid() .'.pdf';
        $snappy->generateFromHtml($html,__DIR__ . '/../../public/pdf/'. $file,['page-size' => 'A4']);

        $order_status->setPoFile($file);

        $this->em->persist($order_status);
        $this->em->flush();

        return $order_status->getPoFile();
    }

    public function clinicSendNotification($order, $distributor, $clinic, $badge)
    {
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
                        data-order-id="'. $order->getId() .'"
                        data-distributor-id="'. $distributor->getId() .'"
                        data-clinic-id="'. $clinic->getId() .'"
                        class="order_notification_alert"
                    >
                        <span class="badge bg-success me-3">'. $badge .'</span>
                    </a>
                </td>
                <td>
                    <a 
                        href="#"
                        data-order-id="'. $order->getId() .'"$notification
                        data-distributor-id="'. $distributor->getId() .'"
                        data-clinic-id="'. $clinic->getId() .'"
                        class="order_notification_alert"
                    >
                        PO No. '. $distributor->getPoNumberPrefix() .'-'. $order->getId() .'
                    </a>
                </td>
                <td>
                    <a 
                        href="#" class="delete-notification" 
                        data-notification-id="'. $notification->getId() .'"
                        data-order-id="'. $order->getId() .'"
                        data-distributor-id="'. $distributor->getId() .'"
                    >
                        <i class="fa-solid fa-xmark text-black-25 ms-3 float-end"></i>
                    </a>
                </td>
            </tr>
        </table>';

        $notification->setNotification($message);

        $this->em->persist($notification);
        $this->em->flush();

        // Email Notifications
        $to = $clinic->getEmail();
        $order_url = $this->getParameter('app.base_url') . '/clinics/order/'. $order->getId() .'/'. $distributor->getId();

        $html = 'Please <a href="'. $order_url .'">click here</a> in order to view the progress of your order';

        $email = (new Email())
            ->from($this->getParameter('app.email_from'))
            ->addTo($to)
            ->subject('Fluid Order - PO  '. $order->getOrderItems()[0]->getPoNumber())
            ->html($html);

        $this->mailer->send($email);
    }

    public function distributorSendNotification($order_id, $distributor_id)
    {
        // Email Notifications
        $order = $this->em->getRepository(Orders::class)->find($order_id);
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $to = $distributor->getEmail();
        $order_url = $this->getParameter('app.base_url') . '/distributors/order/'. $order->getId();

        $html = 'Please <a href="'. $order_url .'">click here</a> in order to view the progress of your order';

        $email = (new Email())
            ->from($this->getParameter('app.email_from'))
            ->addTo($to)
            ->subject('Fluid Order - PO  '. $order->getOrderItems()[0]->getPoNumber())
            ->html($html);

        $this->mailer->send($email);
    }
}
