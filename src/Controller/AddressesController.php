<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Clinics;
use App\Entity\Orders;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddressesController extends AbstractController
{
    private $em;
    private $page_manager;
    const ITEMS_PER_PAGE = 10;

    public function __construct(EntityManagerInterface $em, PaginationManager $page_manager)
    {
        $this->em = $em;
        $this->page_manager = $page_manager;
    }

    private function getAddresses($addresses)
    {
        $response = '
        <!-- Addresses -->
        <div class="row">
            <!-- Create New -->
            <div class="col-12 col-md-12 mt-0 ps-0 pe-0">
                <button 
                    type="button" class="btn btn-primary float-end w-sm-100 text-truncate" data-bs-toggle="modal" 
                    data-bs-target="#modal_address" id="address_new"
                >
                    <i class="fa-solid fa-circle-plus"></i> CREATE NEW ADDRESS
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3 mt-3 mt-sm-4" id="order_header">
                <h3 class="text-light text-truncate">Manage Shipping Addresses</h3>
                <span class="d-none d-sm-inline mb-5 mt-2 text-center text-light text-sm-start">
                    Add or remove shipping addresses from the list below.
                    <strong>A valid address is required for purchasing Fluid Commerce items and redeeming Fluid rewards.</strong>
                </span>
            </div>
        </div>';

        if(count($addresses) > 0) {

            $response .= '
            <div class="row d-none d-xl-flex bg-light border-bottom border-right border-left">
                <div class="col-9">
                    <div class="row">
                        <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                            Type
                        </div>
                        <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                            Telephone
                        </div>
                        <div class="col-md-8 pt-3 pb-3 text-primary fw-bold">
                            Address
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
    
                </div>
            </div>
    
            <div id="address_list">';

            $i = 0;

            foreach ($addresses as $address) {

                $class = 'address-icon';
                $class_billing = 'address-icon';
                $i++;

                // Default Shipping Address
                if ($address->getIsDefault() == 1) {

                    $class = 'is-default-address-icon';
                }

                // Default Billing Address
                if($address->getIsDefaultBilling() == 1){

                    $class_billing = 'is-default-address-icon';
                }

                if($address->getType() == 1){

                    $type = 'Billing';

                } else {

                    $type = 'Shipping';
                }

                $response .= '
                <div class="row">
                    <div class="col-12 col-xl-9 bg-light col-cell border-left border-bottom">
                        <div class="row">
                            <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list pt-3 pb-3">Name</div>
                            <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                                ' . $type . '
                            </div>
                            <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list pt-3 pb-3">Telephone</div>
                            <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                                ' . $address->getTelephone() . '
                            </div>
                            <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list pt-3 pb-3">Address</div>
                            <div class="col-8 col-md-10 col-xl-8 t-cell text-truncate border-list pt-3 pb-3">
                                ' . $address->getAddress() . '
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-3 text-center text-sm-start border-right bg-light col-cell border-right border-bottom">
                        <div class="row">
                            <div class="col-12 col-xl-12 t-cell pt-3 pb-3">
                                <a href="" class="float-end address_update" data-address-id="' . $address->getId() . '" data-bs-toggle="modal" data-bs-target="#modal_address">
                                    <i class="fa-solid fa-pen-to-square edit-icon"></i>
                                </a>
                                <a href="" class="delete-icon float-none float-sm-end open-delete-address-modal" data-bs-toggle="modal" data-address-id="' . $address->getId() . '" data-bs-target="#modal_address_delete">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>';

                                if($type == 'Billing') {

                                    $response .= '
                                    <a href="#" class="address_default_billing float-start float-sm-none" data-billing-address-id="' . $address->getId() . '">
                                        <i class="fa-solid fa-star float-end ' . $class_billing . '"></i>
                                    </a>';

                                }

                                if($type == 'Shipping') {

                                    $response .= '
                                    <a href="#" class="address_default float-start float-sm-none" data-address-id="' . $address->getId() . '">
                                        <i class="fa-solid fa-star float-end ' . $class . '"></i>
                                    </a>';
                                }

                            $response .= '
                            </div>
                        </div>
                    </div>
                </div>';
            }

            $response .= '
                </div>
    
                <!-- Modal Manage Address -->
                <div class="modal fade" id="modal_address" tabindex="-1" aria-labelledby="address_delete_label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <form name="form_addresses" id="form_addresses" method="post">
                                ' . $this->getAddressModal()->getContent() . '
                            </form>
                        </div>
                    </div>
                </div>
    
                <!-- Modal Delete Address -->
                <div class="modal fade" id="modal_address_delete" tabindex="-1" aria-labelledby="address_delete_label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <input type="hidden" value="" name="addresses_form[address_id]" id="address_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="address_delete_label">Delete Address</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-12 mb-0">
                                        Are you sure you would like to delete this address? This action cannot be undone.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">CANCEL</button>
                                <button type="button" class="btn btn-danger btn-sm" id="delete_address">DELETE</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Addresses -->';

        } else {

            $response .= '
            <div class="row border-left border-right border-top border-bottom bg-light">
                <div class="col-12 text-center mt-3 mb-3 pt-3 pb-3 text-center">
                    You don\'t have any addresses saved. 
                </div>
            </div>';
        }

        return $response;
    }

    #[Route('/clinics/get-address-modal/{type}', name: 'get_address_modal')]
    public function getCheckoutAddressModal(Request $request): Response
    {
        $type = $request->get('type');
        $clinic = $this->getUser()->getClinic();
        $addresses = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $clinic->getId(),
            'isActive' => 1,
            'type' => $type
        ]);

        $delivery_type = 'Shipping';

        if($type == 1){

            $delivery_type = 'Billing';
        }

        $i = 0;
        $response['existing_shipping_addresses'] = '';

        foreach($addresses as $address){

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

        $response['modal'] = '
        <input type="hidden" value="" name="addresses_form[address_id]" id="address_id">
        <div class="modal-header" id="modal_header_address">
            <h5 class="modal-title" id="address_modal_label">Create an Address</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body modal-body-address-new pb-0 mb-0">
            <div class="row mb-3">
            
                <!-- Address Type -->
                <div class="col-12 col-sm-4 mb-3">
                    <label class="info">Address Type</label>
                    <input type="text" class="form-control" value="'. $delivery_type .'" readonly>
                    <input 
                        type="hidden" 
                        name="addresses_form[type]"
                        id="address_type"
                        class="form-control"
                        value="'. $type .'">
                    <div class="hidden_msg" id="error_address_type">
                        Required Field
                    </div>
                </div>

                <!-- Clinic Name -->
                <div class="col-12 col-sm-4 mb-3">
                    <label class="info">Clinic Name</label>
                    <input
                        type="text"
                        name="addresses_form[clinicName]"
                        id="address_clinic_name"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_clinic_name">
                        Required Field
                    </div>
                </div>

                <!-- Telephone Number -->
                <div class="col-12 col-sm-4 mb-3">
                    <label class="info">Telephone</label>
                    <input
                        type="text"
                        name="addresses_form[telephone]"
                        id="address_telephone"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_telephone">
                        Required Field
                    </div>
                </div>

                <!-- Address Line 1 -->
                <div class="col-12 col-sm-6 mb-3">
                    <label class="info">Address</label>
                    <input
                        type="text"
                        name="addresses_form[address]"
                        id="address_line_1"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_line_1">
                        Required Field
                    </div>
                </div>

                <!-- Suite -->
                <div class="col-6 mb-3">
                    <label class="info">Suite / APT</label>
                    <input
                        type="text"
                        name="addresses_form[suite]"
                        id="address_suite"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_suite">
                        Required Field
                    </div>
                </div>

                <!-- Postal Code -->
                <div class="col-6 col-sm-4 mb-3">
                    <label class="info">Postal Code</label>
                    <input
                        type="text"
                        name="addresses_form[postalCode]"
                        id="address_postal_code"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_postal_code">
                        Required Field
                    </div>
                </div>

                <!-- City -->
                <div class="col-6 col-sm-4 mb-3">
                    <label class="info">City</label>
                    <input
                        type="text"
                        name="addresses_form[city]"
                        id="address_city"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_city">
                        Required Field
                    </div>
                </div>

                <!-- State -->
                <div class="col-6 col-sm-4 mb-3">
                    <label class="info">State</label>
                    <input
                        type="text"
                        name="addresses_form[state]"
                        id="address_state"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_state">
                        Required Field
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-body modal-body-address-existing hidden pb-0 mb-0 pt-0">
            '. $response['existing_shipping_addresses'] .'
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary w-sm-100 mb-3 mb-sm-0 w-sm-100" data-bs-dismiss="modal">CANCEL</button>
            <button type="submit" class="btn btn-primary w-sm-100 mb-sm-0 w-sm-100" id="btn_save_address">SAVE</button>
        </div>';

        return new JsonResponse($response);
    }

    public function getAddressModal(): Response
    {

        $response = '
        <input type="hidden" value="" name="addresses_form[address_id]" id="address_id">
        <div class="modal-header" id="modal_header_address">
            <h5 class="modal-title" id="address_modal_label">Create an Address</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body modal-body-address-new pb-0 mb-0">
            <div class="row mb-3">
            
                <!-- Address Type -->
                <div class="col-12 col-sm-4 mb-3">
                    <label class="info">Address Type</label>
                    <select
                        name="addresses_form[type]"
                        id="address_type"
                        class="form-control"
                    >
                        <option value=""></option>
                        <option value="1" id="option_billing">Billing</option>
                        <option value="2" id="option_shipping">Shipping</option>
                    </select>
                    <div class="hidden_msg" id="error_address_type">
                        Required Field
                    </div>
                </div>

                <!-- Clinic Name -->
                <div class="col-12 col-sm-4 mb-3">
                    <label class="info">Clinic Name</label>
                    <input
                        type="text"
                        name="addresses_form[clinicName]"
                        id="address_clinic_name"
                        class="form-control"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_clinic_name">
                        Required Field
                    </div>
                </div>

                <!-- Telephone Number -->
                <div class="col-12 col-sm-4 mb-3">
                    <label class="info">Telephone</label>
                    <input 
                        type="text" 
                        name="addresses_mobile" 
                        id="address_mobile" 
                        class="form-control" 
                        value=""
                    >
                    <input
                        type="hidden"
                        name="addresses_form[telephone]"
                        id="address_telephone"
                        value=""
                    >
                    <input
                        type="hidden"
                        name="addresses_form[iso_code]"
                        id="address_iso_code"
                        value=""
                    >
                    <input
                        type="hidden"
                        name="addresses_form[intl_code]"
                        id="address_intl_code"
                        value=""
                    >
                    <div class="hidden_msg" id="error_address_telephone">
                        Required Field
                    </div>
                </div>

                <!-- Address Line 1 -->
                <div class="col-12 mb-3">
                    <label class="info">
                        Address
                    </label>
                    <span role="button" class="text-primary float-end d-sm-block" id="btn_map">
                        <img src="/images/google-maps.png" class="google-map-icon">
                        Find on Map
                    </span>
                    <textarea
                        name="addresses_form[address]"
                        id="address_line_1"
                        class="form-control"
                        rows="5"
                    ></textarea>
                    <div class="hidden_msg" id="error_address_line_1">
                        Required Field
                    </div>
                </div>
                
                <!-- Google Map -->
                <div class="col-12 hidden position-relative" id="address_map">
                    '. $this->render('frontend/clinics/map.html.twig')->getContent() .'
                </div>
            </div>
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary w-sm-100 mb-3 mb-sm-0 w-sm-100" data-bs-dismiss="modal">CANCEL</button>
            <button type="submit" class="btn btn-primary w-sm-100 mb-sm-0 w-sm-100" id="btn_save_address">SAVE</button>
        </div>';

        return new Response($response);
    }

    #[Route('/clinics/get-clinic-addresses', name: 'get_clinic_addresses')]
    public function getClinicAddressesAction(Request $request): Response
    {
        $clinic = $this->getUser()->getClinic();
        $addresses = $this->em->getRepository(Addresses::class)->getAddresses($clinic->getId());
        $results = $this->page_manager->paginate($addresses[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->request->get('page_id'), $results);
        $html = $this->getAddresses($results);

        $response = [
            'html' => $html,
            'pagination' => $pagination
        ];
        
        return new JsonResponse($response);
    }

    #[Route('/clinics/get-address', name: 'clinic_get_address')]
    public function clinicsGetAddressAction(Request $request): Response
    {
        $address = $this->em->getRepository(Addresses::class)->find($request->request->get('id'));

        $response = [

            'id' => $address->getId(),
            'clinic_name' => $address->getClinicName(),
            'telephone' => $address->getTelephone(),
            'address_line_1' => $address->getAddress(),
            'suite' => $address->getSuite(),
            'city' => $address->getCity(),
            'state' => $address->getState(),
            'postal_code' => $address->getPostalCode(),
            'type' => $address->getType(),
            'iso_code' => $address->getIsoCode(),
            'intl_code' => $address->getIntlCode(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/update-address', name: 'update_address')]
    public function updateAddressAction(Request $request): Response
    {
        $data = $request->request->get('addresses_form');
        $clinic_id = $this->getUser()->getClinic()->getId();
        $clinic = $this->em->getRepository(Clinics::class)->find($clinic_id);

        $methods = $this->em->getRepository(Clinics::class)->getClinicAddresses($clinic_id);
        $address_id = $data['address_id'];

        if($address_id > 0){

            $clinic_address = $this->em->getRepository(Addresses::class)->find($address_id);
            $flash = '<b><i class="fas fa-check-circle"></i> Address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $clinic_address = new Addresses();
            $flash = '<b><i class="fas fa-check-circle"></i> Address details successfully created.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $clinic_address->setClinic($clinic);
        $clinic_address->setType($data['type']);
        $clinic_address->setClinicName($data['clinicName']);
        $clinic_address->setTelephone($data['telephone']);
        $clinic_address->setAddress($data['address']);
        $clinic_address->setIsDefault(0);
        $clinic_address->setIsActive(1);
        $clinic_address->setIsoCode($data['iso_code']);
        $clinic_address->setIntlCode($data['intl_code']);

        if(empty($methods)){

            $clinic_address->setIsDefault(1);
        }

        $this->em->persist($clinic_address);
        $this->em->flush();

        // Checkout Create New Address
        $checkout_address = '';
        $checkout_address_id = '';
        if($request->request->get('checkout') != null){

            $order_id = $request->request->get('checkout');
            $order = $this->em->getRepository(Orders::class)->find($order_id);

            $order->setAddress($clinic_address);

            $this->em->persist($order);
            $this->em->flush();

            $checkout_address = $clinic_address->getAddress() ."<br>". $clinic_address->getCity() ."<br>". $clinic_address->getPostalCode() ."<br>". $clinic_address->getState();
            $checkout_address_id = $clinic_address->getId();
        }

        $addresses = $this->em->getRepository(Addresses::class)->getAddresses($clinic_id);
        $results = $this->page_manager->paginate($addresses[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->request->get('page_id'), $results);

        $addresses = $this->getAddresses($results);

        $response = [
            'flash' => $flash,
            'addresses' => $addresses,
            'checkout_address' => $checkout_address,
            'checkout_address_id' => $checkout_address_id,
            'pagination' => $pagination,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/default', name: 'clinic_address_default')]
    public function clinicDefaultAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $this->em->getRepository(Clinics::class)->getClinicDefaultAddresses($clinic_id, $address_id);
        $addresses = $this->em->getRepository(Addresses::class)->getAddresses($clinic_id);
        $results = $this->page_manager->paginate($addresses[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->request->get('page_id'), $results);
        $addresses = $this->getAddresses($results);

        $flash = '<b><i class="fas fa-check-circle"></i> Default address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'addresses' => $addresses,
            'flash' => $flash,
            'pagination' => $pagination,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/default-billing', name: 'clinic_billing_address_default')]
    public function clinicDefaultBillingAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $default_address = $this->em->getRepository(Addresses::class)->find($address_id);
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();

        $addresses = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $clinic_id
        ]);

        // Clear default
        foreach($addresses as $address){

            $address->setIsDefaultBilling(0);
            $this->em->persist($address);
        }

        $this->em->flush();

        $default_address->setIsDefaultBilling(1);

        $this->em->persist($default_address);
        $this->em->flush();

        $addresses = $this->em->getRepository(Addresses::class)->getAddresses($clinic_id);
        $results = $this->page_manager->paginate($addresses[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->request->get('page_id'), $results);

        $addresses = $this->getAddresses($results);

        $flash = '<b><i class="fas fa-check-circle"></i> Default address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'addresses' => $addresses,
            'flash' => $flash,
            'pagination' => $pagination,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/delete', name: 'address_delete')]
    public function clinicDeleteAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $address = $this->em->getRepository(Addresses::class)->find($address_id);

        $address->setIsActive(0);

        $this->em->persist($address);
        $this->em->flush();

        $addresses = $this->em->getRepository(Addresses::class)->getAddresses($this->getUser()->getClinic()->getId());
        $results = $this->page_manager->paginate($addresses[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->request->get('page_id'), $results);

        $html = $this->getAddresses($results);

        $flash = '<b><i class="fas fa-check-circle"></i> Address successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'addresses' => $html,
            'flash' => $flash,
            'pagination' => $pagination,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/address', name: 'find_address')]
    public function clinicFindAddress(Request $request): Response
    {
        return $this->render('frontend/clinics/map.html.twig');
    }

    public function getPagination($page_id, $results)
    {
        $current_page = $page_id;
        $last_page = $this->page_manager->lastPage($results);
        $pagination = '';

        if(count($results) > 0) {

            $pagination .= '
            <!-- Pagination -->
            <div class="row">
                <div class="col-12">';

            if ($last_page > 1) {

                $previous_page_no = $current_page - 1;
                $url = '/clinics/addresses';
                $previous_page = $url;

                $pagination .= '
                <nav class="custom-pagination">
                    <ul class="pagination justify-content-center">
                ';

                $disabled = 'disabled';
                $data_disabled = 'true';

                // Previous Link
                if ($current_page > 1) {

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $pagination .= '
                <li class="page-item ' . $disabled . '">
                    <a class="address-pagination" aria-disabled="' . $data_disabled . '" data-page-id="' . $current_page - 1 . '" href="' . $previous_page . '">
                        <span aria-hidden="true">&laquo;</span> <span class="d-none d-sm-inline">Previous</span>
                    </a>
                </li>';

                $is_active = false;

                for ($i = 1; $i <= $last_page; $i++) {

                    $active = '';

                    if ($i == (int)$current_page) {

                        $active = 'active';
                        $is_active = true;
                    }

                    // Go to previous page if all records for a page have been deleted
                    if(!$is_active && $i == count($results)){

                        $active = 'active';
                    }

                    $pagination .= '
                    <li class="page-item ' . $active . '">
                        <a class="address-pagination" data-page-id="' . $i . '" href="' . $url . '">' . $i . '</a>
                    </li>';
                }

                $disabled = 'disabled';
                $data_disabled = 'true';

                if ($current_page < $last_page) {

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $pagination .= '
                <li class="page-item ' . $disabled . '">
                    <a class="address-pagination" aria-disabled="' . $data_disabled . '" data-page-id="' . $current_page + 1 . '" href="' . $url . '">
                        <span class="d-none d-sm-inline">Next</span> <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>';

                if(count($results) < $current_page){

                    $current_page = count($results);
                }

                $pagination .= '
                        </ul>
                    </nav>
                    <input type="hidden" id="page_no" value="' . $current_page . '">
                </div>';
            }
        }

        return $pagination;
    }
}
