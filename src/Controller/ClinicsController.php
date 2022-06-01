<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Baskets;
use App\Entity\ClinicCommunicationMethods;
use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\CommunicationMethods;
use App\Entity\Distributors;
use App\Entity\DistributorUsers;
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
                $clinic_users->setRoles(['ROLE_CLINIC']);
                $clinic_users->setPassword($hashed_pwd);
                $clinic_users->setIsPrimary(1);

                $this->em->persist($clinic_users);

                // Create Default Basket
                $basket = new Baskets();

                $basket->setClinic($clinic);
                $basket->setName('Fluid Commerce');
                $basket->setTotal(0);
                $basket->setStatus('active');
                $basket->setSavedBy($clinic_users->getFirstName() .' '. $clinic_users->getLastName());

                $this->em->persist($basket);

                // Create In App Communication Method
                $clinic_communication_method = new ClinicCommunicationMethods();
                $communication_method = $this->em->getRepository(CommunicationMethods::class)->find(1);

                $clinic_communication_method->setClinic($clinic);
                $clinic_communication_method->setCommunicationMethod($communication_method);
                $clinic_communication_method->setIsDefault(1);
                $clinic_communication_method->setIsActive(1);

                $this->em->persist($clinic_communication_method);
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

            $response = 'Your Fluid account was successfully created, an email with your login credentials has been sent to your inbox.';

        } else {

            $response = false;
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/register/check-email', name: 'clinic_check_email')]
    public function clinicsCheckEmailAction(Request $request): Response
    {
        $email = $request->request->get('email');
        $response = true;

        $distributor = $this->em->getRepository(Distributors::class)->findOneBy([
            'email' => $email
        ]);
        $distributor_users = $this->em->getRepository(DistributorUsers::class)->findOneBy([
            'email' => $email
        ]);
        $clinic = $this->em->getRepository(Clinics::class)->findOneBy([
            'email' => $email
        ]);
        $clinic_users = $this->em->getRepository(ClinicUsers::class)->findOneBy([
            'email' => $email
        ]);

        if($distributor != null || $distributor_users != null || $clinic | null || $clinic_users != null){

            $response = false;
        }

        return new JsonResponse($response);
    }

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
            $clinics->setIsoCode($data['iso_code']);
            $clinics->setIntlCode($data['intl_code']);

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
        <div class="row position-relative" id="account_settings">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3" id="order_header">
                <h3 class="text-light">Account & Settings</h3>
                <span class="mb-5 mt-2 text-center text-light text-sm-start">
                    Clinic Information
                </span>
            </div>
            <form name="form_clinic_information" id="form_clinic_information" method="post">
                <input type="checkbox" name="call_back_form[contact_me_by_fax_only]" value="1" tabindex="-1" class="hidden" autocomplete="off">
        
                <div class="row pb-3 pt-2 border-left border-right bg-light">
        
                    <!-- Clinic name -->
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
        
                <div class="row pb-3 border-left border-right bg-light">
        
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
                            type="hidden"
                            name="isocode" 
                            id="isocode" 
                            value="'. $clinic->getIsoCode() .'"
                        >
                        <input 
                            type="hidden"
                            name="clinic_form[telephone]" 
                            id="clinic_telephone" 
                            value="'. $clinic->getTelephone() .'"
                        >
                        <input 
                            type="text" 
                            name="mobile" 
                            id="mobile" 
                            name="mobile" 
                            class="form-control" 
                            placeholder="Telephone*"
                            value="'. $clinic->getTelephone() .'"
                        >
                        <div class="hidden_msg" id="error_telephone">
                            Required Field
                        </div>
                    </div>
                </div>
        
                <div class="row pt-5 pb-3 border-left border-right border-bottom bg-light">
        
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
                    <div class="col-12 mt-2 mb-5 ps-0 pe-0">
                        <button id="btn_personal_information" type="submit" class="btn btn-primary w-100">UPDATE CLINIC INFORMATION</button>
                    </div>
                </div>
            </form>
        </div>';

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
