<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Clinics;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddressesController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function getAddresses($addresses)
    {
        $response = '
        <!-- Addresses -->
        <div class="row">
            <div class="col-12 col-md-6 mb-3 text-center text-sm-start">
                <h3>Manage Shipping Addresses</h3>
            </div>
            <!-- Create New -->
            <div class="col-12 col-md-6 mb-3 mt-0">
                <button 
                    type="button" class="btn btn-primary float-end w-sm-100" data-bs-toggle="modal" 
                    data-bs-target="#modal_address" id="address_new"
                >
                    <i class="fa-solid fa-circle-plus"></i> CREATE NEW ADDRESS
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-5 mt-2 info text-center text-sm-start">
                Add or remove shipping addresses from the list below.
                <strong>A valid address is required for purchasing Fluid Commerce items and redeeming Fluid rewards.</strong>
            </div>
        </div>

            <div class="row d-none d-xl-flex ms-1 me-1 ms-md0 me-md-0">
                <div class="col-10">
                    <div class="row">
                        <div class="col-md-2 t-header">
                            Name
                        </div>
                        <div class="col-md-2 t-header">
                            Telephone
                        </div>
                        <div class="col-md-4 t-header">
                            Address Line 1
                        </div>
                        <div class="col-md-2 t-header">
                            City
                        </div>
                        <div class="col-md-2 t-header">
                            State
                        </div>
                    </div>
                </div>
                <div class="col-md-1 t-header">
                    Zip
                </div>
                <div class="col-md-1 t-header">

                </div>
            </div>

            <div id="address_list" style="width: calc(100% - 1px)">';

        $i = 0;

        foreach($addresses as $address) {

            $class = 'address-icon';
            $border_top = '';
            $i++;

            if($i == 1){

                $border_top = 'style="border-top: 1px solid #d3d3d4"';
            }

            if($address->getIsDefault() == 1){

                $class = 'is-default-address-icon';
            }

            $response .= '
                    <div class="row t-row ms-1 me-1 ms-md-0 me-md-0" '. $border_top .'>
                        <div class="col-12 col-xl-10">
                            <div class="row">
                                <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list">Name</div>
                                <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list">
                                    '. $address->getClinicName() .'
                                </div>
                                <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list">Telephone</div>
                                <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list">
                                    '. $address->getTelephone() .'
                                </div>
                                <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list">Address</div>
                                <div class="col-8 col-md-10 col-xl-4 t-cell text-truncate border-list">
                                    '. $address->getAddress() .'
                                </div>
                                <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list">City</div>
                                <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list">
                                    '. $address->getCity() .'
                                </div>
                                <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary border-list">State</div>
                                <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list">
                                    '. $address->getState() .'
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-2 text-center text-sm-start">
                            <div class="row">
                                <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-start border-list">Zip</div>
                                <div class="col-8 col-md-4 t-cell text-truncate text-start border-list">
                                    '. $address->getPostalCode() .'
                                </div>
                                <div class="col-12 col-xl-8 t-cell">
                                    <a href="" class="float-end address_update" data-address-id="'. $address->getId() .'" data-bs-toggle="modal" data-bs-target="#modal_address">
                                        <i class="fa-solid fa-pen-to-square edit-icon"></i>
                                    </a>
                                    <a href="" class="delete-icon float-none float-sm-end open-delete-address-modal" data-bs-toggle="modal" data-address-id="'. $address->getId() .'" data-bs-target="#modal_address_delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                    <a href="#" class="address_default float-start float-sm-none" data-address-id="'. $address->getId() .'">
                                        <i class="fa-solid fa-star float-end '. $class .'"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>';
        }
        $response .= '
            </div>

            <!-- Modal Manage Address -->
            <div class="modal fade" id="modal_address" tabindex="-1" aria-labelledby="address_delete_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form name="form_addresses" id="form_addresses" method="post">
                            <input type="hidden" value="" name="addresses_form[address_id]" id="address_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="address_modal_label">Create an Address</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pb-0 mb-0">
                                <div class="row mb-3">
    
                                    <!-- Clinic Name -->
                                    <div class="col-12 col-sm-6 mb-3">
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
                                    <div class="col-12 col-sm-6 mb-3">
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
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary w-sm-100 mb-3 mb-sm-0 w-sm-100" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="btn btn-primary w-sm-100 mb-sm-0 w-sm-100">SAVE</button>
                            </div>
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

        return $response;
    }

    #[Route('/clinics/get-clinic-addresses', name: 'get_clinic_addresses')]
    public function getClinicAddressesAction(): Response
    {
        $clinic = $this->getUser()->getClinic();
        $addresses = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $clinic->getId(),
            'isActive' => 1,
        ]);
        
        $response = $this->getAddresses($addresses);
        
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
            'postal_code' => $address->getPostalCode()
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/update-address', name: 'update_address')]
    public function updateAddressAction(Request $request): Response
    {
        $data = $request->request->get('addresses_form');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $methods = $this->em->getRepository(Clinics::class)->getClinicAddresses($clinic->getId());
        $address_id = $data['address_id'];

        if($address_id == 0){

            $clinic_address = new Addresses();
            $flash = '<b><i class="fas fa-check-circle"></i> Address details successfully created.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $clinic_address = $this->em->getRepository(Addresses::class)->find($address_id);
            $flash = '<b><i class="fas fa-check-circle"></i> Address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $clinic_address->setClinic($clinic);
        $clinic_address->setClinicName($data['clinicName']);
        $clinic_address->setTelephone($data['telephone']);
        $clinic_address->setAddress($data['address']);
        $clinic_address->setSuite($data['suite']);
        $clinic_address->setPostalCode($data['postalCode']);
        $clinic_address->setCity($data['city']);
        $clinic_address->setState($data['state']);
        $clinic_address->setIsDefault(0);
        $clinic_address->setIsActive(1);

        if(empty($methods)){

            $clinic_address->setIsDefault(1);
        }

        $this->em->persist($clinic_address);
        $this->em->flush();

        $addresses = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $clinic->getId(),
            'isActive' => 1,
        ]);

        $addresses = $this->getAddresses($addresses);

        $response = [
            'flash' => $flash,
            'addresses' => $addresses
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/default', name: 'clinic_address_default')]
    public function clinicDefaultAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $methods = $this->em->getRepository(Clinics::class)->getClinicDefaultAddresses($clinic_id, $address_id);

        $addresses = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $clinic_id,
            'isActive' => 1,
        ]);

        $addresses = $this->getAddresses($addresses);

        $flash = '<b><i class="fas fa-check-circle"></i> Default address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'addresses' => $addresses,
            'flash' => $flash
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

        $addresses = $this->em->getRepository(Addresses::class)->findBy([
            'clinic' => $this->getUser()->getClinic()->getId(),
            'isActive' => 1
        ]);

        $addresses = $this->getAddresses($addresses);

        $flash = '<b><i class="fas fa-check-circle"></i> Address successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'addresses' => $addresses,
            'flash' => $flash
        ];

        return new JsonResponse($response);
    }
}
