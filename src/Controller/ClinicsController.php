<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\ClinicCommunicationMethods;
use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\CommunicationMethods;
use App\Entity\ListItems;
use App\Entity\Lists;
use App\Entity\ProductNotes;
use App\Entity\Products;
use App\Form\AddressesFormType;
use App\Form\ClinicCommunicationMethodsFormType;
use App\Form\ClinicFormType;
use App\Form\ClinicUsersFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ClinicsController extends AbstractController
{
    const ITEMS_PER_PAGE = 12;
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/clinics/register', name: 'clinic_reg')]
    public function clinicReg(Request $request): Response
    {
        $clinics = new Clinics();
        $clinic_users = new ClinicUsers();

        $clinics->getClinicUsers()->add($clinic_users);

        $form = $this->createForm(ClinicFormType::class, $clinics)->createView();

        return $this->render('frontend/clinics/register.html.twig', [
            'form' => $form,
        ]);
    }

    protected function createClinicForm()
    {
        $clinics = new Clinics();

        return $this->createForm(ClinicFormType::class, $clinics);
    }

    public function createClinicsAddressesForm()
    {
        $methods = new Addresses();

        return $this->createForm(AddressesFormType::class, $methods);
    }

    public function createClinicCommunicationMethodsForm()
    {
        $communication_methods = new ClinicCommunicationMethods();

        return $this->createForm(ClinicCommunicationMethodsFormType::class, $communication_methods);
    }

    public function createClinicUserForm()
    {
        $clinic_users = new ClinicUsers();

        return $this->createForm(ClinicUsersFormType::class, $clinic_users);
    }

    #[Route('/clinics/register/create', name: 'clinic_create')]
    public function clinicsCreateAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request;
        $clinics = $this->em->getRepository(Clinics::class)->findOneBy(['email' => $data->get('email')]);

        if($clinics == null) {

            $clinics = new Clinics();

            $plain_text_pwd = $this->generatePassword();

            if (!empty($plain_text_pwd)) {

                $clinics->setClinicName($data->get('clinicName'));
                $clinics->setEmail($data->get('email'));
                $clinics->setTelephone($data->get('clinicTelephone'));

                $this->em->persist($clinics);
                $this->em->flush();

                // Create user
                $clinic = $this->em->getRepository(Clinics::class)->findOneBy(['email' => $data->get('email')]);
                $clinic_users = new ClinicUsers();

                $hashed_pwd = $passwordHasher->hashPassword($clinic_users, $plain_text_pwd);

                $clinic_users->setClinic($clinic);
                $clinic_users->setFirstName($data->get('firstName'));
                $clinic_users->setLastName($data->get('lastName'));
                $clinic_users->setPosition($data->get('position'));
                $clinic_users->setEmail($data->get('email'));
                $clinic_users->setTelephone($data->get('telephone'));
                $clinic_users->setRoles(['ROLE_USER']);
                $clinic_users->setPassword($hashed_pwd);
                $clinic_users->setIsPrimary(1);

                $this->em->persist($clinic_users);
                $this->em->flush();

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $clinic_users->getFirstName() .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/clinics/login">https://'. $_SERVER['HTTP_HOST'] .'/clinics/login</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $clinic_users->getUsername() .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plain_text_pwd .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($data->get('email'))
                    ->subject('Fluid Login Credentials')
                    ->html($body);

                $mailer->send($email);
            }

            $response = true;

        } else {

            $response = false;
        }

        return new JsonResponse($response);
    }

//    #[Route('/clinics/dashboard', name: 'clinic_dashboard')]
//    public function clinicsDashboardAction(Request $request): Response
//    {
//        if($this->get('security.token_storage')->getToken() == null){
//
//            $this->addFlash('danger', 'Your session expired due to inactivity, please login.');
//
//            return $this->redirectToRoute('clinics_login');
//        }
//
//        $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
//        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
//        $clinics = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
//        $communication_methods = $clinics->getClinicCommunicationMethods()[0];
//        $form = $this->createClinicForm()->createView();
//        $user_form = $this->createClinicUserForm()->createView();
//        $address_form = $this->createClinicsAddressesForm()->createView();
//        $communication_methods_form = $this->createClinicCommunicationMethodsForm()->createView();
//        $communication_methods_message = '';
//
//        if($communication_methods == null){
//
//            $communication_methods_message = '<p><i>You do not currently have any communication methods created. Add a new communication method below</i></p>';
//        }
//
//        return $this->render('frontend/clinics/dashboard.html.twig',[
//            'clinic' => $clinics,
//            'form' => $form,
//            'address_form' => $address_form,
//            'communication_methods_form' => $communication_methods_form,
//            'user' => $user,
//            'user_form' => $user_form,
//            'communication_methods_message' => $communication_methods_message,
//            'communication_methods' => $communication_methods,
//        ]);
//    }

    #[Route('/clinics/update/personal-information', name: 'clinic_update_personal_information')]
    public function clinicsUpdatePersonalInformationAction(Request $request): Response
    {
        $data = $request->request;
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $clinics = $this->em->getRepository(Clinics::class)->findOneBy(['email' => $username]);

        if($clinics != null) {

            $clinics->setFirstName($data->get('first_name'));
            $clinics->setLastName($data->get('last_name'));
            $clinics->setTelephone($data->get('telephone'));
            $clinics->setPosition($data->get('position'));

            $this->em->persist($clinics);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Personal details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/update/company-information', name: 'clinic_update_company_information')]
    public function clinicsUpdateCompanyInformationAction(Request $request): Response
    {
        $data = $request->request->get('clinic_form');
        $username = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getEmail();
        $clinics = $this->em->getRepository(Clinics::class)->findOneBy(['email' => $username]);

        if($clinics != null) {

            $clinics->setClinicName($data['clinicName']);
            $clinics->setEmail($data['email']);
            $clinics->setTelephone($data['telephone']);

            $this->em->persist($clinics);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Company details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/get-company-information', name: 'get_company_information')]
    public function clinicsGetCompanyInformationAction(Request $request): Response
    {
        $clinic = $this->getUser()->getClinic();

        $response = '
        <div class="row" id="account_settings">
            <div class="col-12 mb-3">
                <h3>Account & Settings</h3>
            </div>
            <div class="col-12 mb-3 mt-2">
                <h5>Clinic Information</h5>
            </div>
            <form name="form_clinic_information" id="form_clinic_information" method="post">
                <input type="checkbox" name="call_back_form[contact_me_by_fax_only]" value="1" tabindex="-1" class="hidden" autocomplete="off">
        
                <div class="row mb-3">
        
                    <!-- First name -->
                    <div class="col-12 col-sm-12">
                        <label>
                            Clinic Name
                        </label>
                        <input 
                            type="text" 
                            name="clinic_form[clinicName]" 
                            id="clinic_name" 
                            class="form-control" 
                            placeholder="Clinic Name*"
                            value="'. $clinic->getClinicName() .'"
                        >
                        <div class="hidden_msg" id="error_first_name">
                            Required Field
                        </div>
                    </div>
                </div>
        
                <div class="row mb-3">
        
                    <!-- Email -->
                    <div class="col-12 col-sm-6">
                        <label>
                            Clinic Email Address
                        </label>
                        <input 
                            type="text" 
                            name="clinic_form[email]" 
                            id="clinic_email" 
                            class="form-control" 
                            placeholder="Clinic Email*"
                            value="'. $clinic->getEmail() .'"
                        >
                        <div class="hidden_msg" id="error_clinic_email">
                            Required Field
                        </div>
                    </div>
        
                    <!-- Telephone -->
                    <div class="col-12 col-sm-6">
                        <label>Enter Your Telephone*</label>
                        <input 
                            type="text" 
                            name="clinic_form[telephone]" 
                            id="clinic_telephone" 
                            class="form-control" 
                            placeholder="Telephone*"
                            value="'. $clinic->getTelephone() .'"
                        >
                        <div class="hidden_msg" id="error_telephone">
                            Required Field
                        </div>
                    </div>
                </div>
        
                <div class="row mt-5 mb-3">
        
                    <label class="mb-4 d-block">
                        Select All Species Treated By Your Practice
                    </label>
        
                    <!-- Canine -->
                    <div class="col-6 col-sm-4 col-md-2 text-center">
                        <div class="custom-control custom-checkbox image-checkbox" style="position: relative">
                            <input type="checkbox" class="custom-control-input species-checkbox" id="species_canine">
                            <label class="custom-control-label" for="species_canine">
                                <i class="fa-solid fa-dog species-icon" id="icon_canine"></i>
                            </label>
                        </div>
                    </div>
        
                    <!-- Feline -->
                    <div class=" col-6 col-sm-4 col-md-2 text-center">
                        <div class="custom-control custom-checkbox image-checkbox" style="position: relative">
                            <input type="checkbox" class="custom-control-input species-checkbox" id="species_feline">
                            <label class="custom-control-label" for="species_feline">
                                <i class="fa-solid fa-cat species-icon" id="icon_feline"></i>
                            </label>
                        </div>
                    </div>
        
                    <!-- Equine -->
                    <div class="mt-5 mt-sm-0 col-6 col-sm-4 col-md-2 text-center">
                        <div class="custom-control custom-checkbox image-checkbox" style="position: relative">
                            <input type="checkbox" class="custom-control-input species-checkbox" id="species_equine">
                            <label class="custom-control-label" for="species_equine">
                                <i class="fa-solid fa-horse species-icon" id="icon_equine"></i>
                            </label>
                        </div>
                    </div>
        
                    <!-- Bovine -->
                    <div class="mt-5 mt-sm-5 mt-md-0 col-6 col-sm-4 col-md-2 text-center">
                        <div class="custom-control custom-checkbox image-checkbox" style="position: relative">
                            <input type="checkbox" class="custom-control-input species-checkbox" id="species_bovine">
                            <label class="custom-control-label" for="species_bovine">
                                <i class="fa-solid fa-hippo species-icon" id="icon_bovine"></i>
                            </label>
                        </div>
                    </div>
        
                    <!-- Porcine -->
                    <div class="mt-5 mt-sm-5 mt-md-0 col-6 col-sm-4 col-md-2 text-center">
                        <div class="custom-control custom-checkbox image-checkbox" style="position: relative">
                            <input type="checkbox" class="custom-control-input species-checkbox" id="species_porcine">
                            <label class="custom-control-label" for="species_porcine">
                                <i class="fa-solid fa-piggy-bank species-icon" id="icon_porcine"></i>
                            </label>
                        </div>
                    </div>
        
                    <!-- Exotic -->
                    <div class="mt-5 mt-sm-5 mt-md-0 col-6 col-sm-4 col-md-2 text-center">
                        <div class="custom-control custom-checkbox image-checkbox" style="position: relative">
                            <input type="checkbox" class="custom-control-input species-checkbox" id="species_exotic">
                            <label class="custom-control-label" for="species_exotic">
                                <i class="fa-solid fa-dragon species-icon" id="icon_exotic"></i>
                            </label>
                        </div>
                    </div>
        
                </div>
        
                <div class="row mb-3">
                    <div class="col-12 mt-3 mb-5">
                        <button id="btn_personal_information" type="submit" class="btn btn-primary w-100">UPDATE CLINIC INFORMATION</button>
                    </div>
                </div>
            </form>
        </div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/get-product-notes', name: 'clinic_get_product_notes')]
    public function clinicGetProductNotes(Request $request): Response
    {
        $product_id = $request->request->get('product_id');
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $notes = $this->em->getRepository(ProductNotes::class)->findNotes($product_id, $clinic_id);
        $response = false;

        if(!empty($notes)) {

            $response = [
                'note' => $notes[0]->getNote(),
                'from' => $notes[0]->getClinicUser()->getFirstName() .' '. $notes[0]->getClinicUser()->getLastName(),
            ];
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/addresse-refresh', name: 'clinic_refresh_addresses')]
    public function clinicRefreshAddressesAction(Request $request): Response
    {
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $methods = $this->em->getRepository(Clinics::class)->getClinicAddresses($clinic_id);

        $html = '';

        foreach($methods[0]->getAddresses() as $address){

            $class = 'address-icon';

            if($address->getIsDefault() == 1){

                $class = 'is-default-address-icon';
            }

            $html .= '<div class="row t-row">
                    <div class="col-md-10" id="string_address_clinic_name_'. $address->getId() .'">
                        <div class="row">
                            <div class="col-md-2 t-cell text-truncate" id="string_address_clinic_name_'. $address->getId() .'">
                                '. $address->getClinicName() .'
                            </div>
                            <div class="col-md-2 t-cell text-truncate" id="string_address_telephone_'. $address->getId() .'">
                                '. $address->getTelephone() .'
                            </div>
                            <div class="col-md-4 t-cell text-truncate" id="string_address_address_'. $address->getId() .'">
                                '. $address->getAddress() .'
                            </div>
                            <div class="col-md-2 t-cell text-truncate" id="string_address_city_'. $address->getId() .'">
                                '. $address->getCity() .'
                            </div>
                            <div class="col-md-2 t-cell text-truncate" id="string_address_state_'. $address->getId() .'">
                                '. $address->getState() .'
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2" id="string_address_postal)_code'. $address->getId() .'">
                        <div class="row">
                            <div class="col-md-4 t-cell text-truncate" id="string_address_postal_code'. $address->getId() .'">
                                '. $address->getPostalCode() .'
                            </div>
                            <div class="col-md-8 t-cell">
                                <a href="" class="float-end" data-bs-toggle="modal" data-bs-target="#modal_address" id="address_update_'. $address->getId() .'">
                                    <i class="fa-solid fa-pen-to-square edit-icon"></i>
                                </a>
                                <a href="" class="delete-icon float-end" data-bs-toggle="modal" data-value="'. $address->getId() .'" data-bs-target="#modal_address_delete" id="address_delete_'. $address->getId() .'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                                <a href="#" id="address_default_'. $address->getId() .'">
                                    <i class="fa-solid fa-star float-end '. $class.'"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';
        }

        return new JsonResponse($html);
    }

    #[Route('/clinics/communication-refresh', name: 'clinic_refresh_communication')]
    public function clinicRefreshCommunicationMethodsAction(Request $request): Response
    {
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $methods = $this->em->getRepository(Clinics::class)->getClinicCommunicationMethods($clinic_id);

        $html = '';
        //dd($methods[0]->getClinicCommunicationMethods());
        foreach($methods[0]->getClinicCommunicationMethods() as $method){

            $html .= '<div class="row t-row">
                            <div class="col-md-4 t-cell text-truncate" id="">
                                '. $method->getCommunicationMethod()->getMethod() .'
                            </div>
                            <div class="col-md-4 t-cell text-truncate" id="">
                                '. $method->getSendTo() .'
                            </div>
                            <div class="col-md-4 t-cell text-truncate" id="">
                                <a href="" class="float-end" data-bs-toggle="modal" data-bs-target="#modal_communication_methods" id="communication_method_update_'. $method->getId() .'">
                                    <i class="fa-solid fa-pen-to-square edit-icon"></i>
                                </a>
                                <a href="" class="delete-icon float-end" data-bs-toggle="modal" data-value="'. $method->getId() .'" data-bs-target="#modal_method_delete" id="method_delete_'. $method->getId() .'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>';
        }

        return new JsonResponse($html);
    }

    #[Route('/clinics/inventory/get-lists', name: 'inventory_get_lists')]
    public function clinicsGetListsAction(Request $request): Response
    {
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $lists = $this->em->getRepository(Lists::class)->getClinicLists($clinic->getId());

        $product_id = (int) $request->request->get('id');

        $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3>';

        if(count($lists) == 0){

            $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3><p id="lists_no_data">You do not currently have any 
            shopping lists on Fluid<br><br>Have shopping lists with your suppliers? We\'ll import them! Send us a message 
            using the chat icon in the lower right corner and we will help import you lists! You can also create new lists 
            using the Create List button below</p>';

        } else {

            for($i = 0; $i < count($lists); $i++){

                if(count($lists[$i]->getListItems()) > 0) {

                    $item_count = true;

                    $item_id = $lists[$i]->getListItems()[0]->getList()->getListItems()[0]->getId();
                    $is_selected = false;

                    for($c = 0; $c < count($lists[$i]->getListItems()); $c++){

                        if($lists[$i]->getListItems()[$c]->getProduct()->getId() == $product_id){

                            $is_selected = true;
                            break;
                        }
                    }

                    if($is_selected) {

                        $icon = '<a href="" class="list_remove_item" data-id="' . $product_id . '" data-value="' . $item_id . '">
                            <i class="fa-solid fa-circle-check pe-2 list-icon list-icon-checked"></i>
                        </a>';

                    } else {

                        $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                    }

                } else {

                    $item_count = false;

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

                $response .= $this->getListRow($icon, $lists[$i]->getName(), $lists[$i]->getId(), $item_count);
            }
        }

        $response .= $this->listCreateNew($product_id);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/remove-list-item', name: 'inventory_remove_list_item')]
    public function clinicsRemoveListsItemAction(Request $request): Response
    {
        $item_id = $request->request->get('id');
        $list_item = $this->em->getRepository(ListItems::class)->find($item_id);

        $this->em->remove($list_item);
        $this->em->flush();

        $response = $this->clinicsGetListsAction($request);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/remove-list-item', name: 'inventory_remove_list_item')]
    public function clinicsAddListsItemAction(Request $request): Response
    {
        $item_id = $request->request->get('item_id');
        $list_item = $this->em->getRepository(ListItems::class)->find($item_id);

        $this->em->remove($list_item);
        $this->em->flush();

        $response = $this->clinicsGetListsAction($request);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/manage-list', name: 'inventory_manage_list')]
    public function clinicsManageListAction(Request $request): Response
    {
        $data = $request->request;
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $products = $this->em->getRepository(Products::class)->find($data->get('product_id'));

        $product_id = (int) $data->get('product_id');
        $list_id = (int) $data->get('list_id');
        $list_type = $data->get('list_type');
        $list_name = $data->get('list_name');

        // List
        if($list_id == 0){

            $list = new Lists();

            $list->setItemCount(1);
            $list->setListType($list_type);
            $list->setClinic($clinic);

            $list->setName($list_name);

            $this->em->persist($list);
            $this->em->flush();

        } else {

            $list = $this->em->getRepository(Lists::class)->find($list_id);
        }

        // List item
        $list_item = new ListItems();

        $list_item->setList($list);
        $list_item->setProduct($products);
        $list_item->setName($products->getName());

        $this->em->persist($list_item);
        $this->em->flush();

        $lists = $this->em->getRepository(Lists::class)->getClinicLists($clinic->getId($data->get('product_id')));

        $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3>';

        for($i = 0; $i < count($lists); $i++){

            if(count($lists[$i]->getListItems()) > 0) {

                $item_count = true;

                $item_id = $lists[$i]->getListItems()[0]->getList()->getListItems()[0]->getId();
                $is_selected = false;

                for($c = 0; $c < count($lists[$i]->getListItems()); $c++){
                    dump($lists[$i]->getListItems()[$c]->getProduct()->getId(),$product_id);
                    if($lists[$i]->getListItems()[$c]->getProduct()->getId() == $product_id){

                        $is_selected = true;
                        break;
                    }
                }

                if($is_selected) {

                    $icon = '<a href="" class="list_remove_item" data-id="' . $product_id . '" data-value="' . $item_id . '">
                            <i class="fa-solid fa-circle-check pe-2 list-icon list-icon-checked"></i>
                        </a>';

                } else {

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

            } else {

                $item_count = false;

                $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
            }

            $response .= $this->getListRow($icon, $lists[$i]->getName(), $lists[$i]->getId(), $item_count);
        }

        $response .= $this->listCreateNew($product_id);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/delete-list-item', name: 'inventory_delete_list_item')]
    public function clinicsDeleteListItemAction(Request $request): Response
    {
        $data = $request->request;
        $product_id = (int) $data->get('product_id');
        $list_id = (int) $data->get('list_id');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $list_item = $this->em->getRepository(ListItems::class)->find($list_id);
        
        $this->em->remove($list_item);
        $this->em->flush();

        $lists = $this->em->getRepository(Lists::class)->getClinicLists($clinic->getId());

        $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3>';

        for($i = 0; $i < count($lists); $i++){

            if(count($lists[$i]->getListItems()) > 0) {

                $item_count = true;

                $item_id = $lists[$i]->getListItems()[0]->getList()->getListItems()[0]->getId();
                $is_selected = false;

                for($c = 0; $c < count($lists[$i]->getListItems()); $c++){
                    dump($lists[$i]->getListItems()[$c]->getProduct()->getId(),$product_id);
                    if($lists[$i]->getListItems()[$c]->getProduct()->getId() == $product_id){

                        $is_selected = true;
                        break;
                    }
                }

                if($is_selected) {

                    $icon = '<a href="" class="list_remove_item" data-id="' . $product_id . '" data-value="' . $item_id . '">
                            <i class="fa-solid fa-circle-check pe-2 list-icon list-icon-checked"></i>
                        </a>';

                } else {

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

            } else {

                $item_count = false;

                $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
            }

            $response .= $this->getListRow($icon, $lists[$i]->getName(), $lists[$i]->getId(), $item_count);
        }

        $response .= $this->listCreateNew($product_id);

        return new JsonResponse($response);
    }

    private function getListRow($icon, $list_name, $list_id, $item_count){

        if($item_count){

            $link = '<a href="" class="float-end view-list" data-list-id="'. $list_id .'">View List</a>';

        } else {

            $link = '<span class="float-end view-list disabled">View List</span>';
        }

        return '
                <div class="row p-2">
                    <div class="col-8 col-sm-10 ps-1 d-flex flex-column">
                        <table style="height: 30px;">
                            <tr>
                                <td class="align-middle" width="50px">
                                    '. $icon .'
                                </td>
                                <td class="align-middle info">
                                    '. $list_name .'
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-4 col-sm-2">
                        '. $link .'
                    </div>
                </div>
            ';
    }

    private function listCreateNew($product_id)
    {
        return '
            <div class="row mt-4">
                <div class="col-12 col-sm-6">
                    <form name="form_list" id="form_list" method="post">
                        <input type="hidden" name="product_id" value="'. $product_id .'">
                        <input type="hidden" name="list_id" value="0">
                        <input type="hidden" name="list_type" value="custom">
                        <div class="row">
                            <div class="col-12 col-sm-8">
                                <input type="text" name="list_name" id="list_name" class="form-control mb-3 mb-sm-0">
                                <div class="hidden_msg" id="error_list_name">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <button type="submit" class="btn btn-primary mb-3 mb-sm-0 w-100 w-sm-100" id="list_create_new">
                                    <i class="fa-solid fa-circle-plus"></i>
                                    &nbsp;CREATE NEW
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-12 col-sm-6">
                    <a href="" class="btn btn-secondary float-end w-100 w-sm-100">
                        VIEW AND MANAGE YOUR LISTS 
                    </a>
                </div>
            </div>';
    }

    private function generatePassword()
    {
        $sets = [];
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $sets[] = '23456789';
        $sets[] = '!@$%*?';

        $all = '';
        $password = '';

        foreach ($sets as $set) {

            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);

        for ($i = 0; $i < 16 - count($sets); $i++) {

            $password .= $all[array_rand($all)];
        }

        $this->plain_password = str_shuffle($password);

        return $this->plain_password;
    }
}
