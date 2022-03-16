<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\ClinicCommunicationMethods;
use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\CommunicationMethods;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\ListItems;
use App\Entity\Lists;
use App\Entity\ProductNotes;
use App\Entity\Products;
use App\Form\AddressesFormType;
use App\Form\ClinicCommunicationMethodsFormType;
use App\Form\ClinicFormType;
use App\Form\ClinicUsersFormType;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use function Sodium\add;

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

    #[Route('/clinics/dashboard', name: 'clinic_dashboard')]
    public function clinicsDashboardAction(Request $request): Response
    {
        if($this->get('security.token_storage')->getToken() == null){

            $this->addFlash('danger', 'Your session expired due to inactivity, please login.');

            return $this->redirectToRoute('clinics_login');
        }

        $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $clinics = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $communication_methods = $clinics->getClinicCommunicationMethods()[0];
        $form = $this->createClinicForm()->createView();
        $user_form = $this->createClinicUserForm()->createView();
        $address_form = $this->createClinicsAddressesForm()->createView();
        $communication_methods_form = $this->createClinicCommunicationMethodsForm()->createView();
        $communication_methods_message = '';

        if($communication_methods == null){

            $communication_methods_message = '<p><i>You do not currently have any communication methods created. Add a new communication method below</i></p>';
        }

        return $this->render('frontend/clinics/dashboard.html.twig',[
            'clinic' => $clinics,
            'form' => $form,
            'address_form' => $address_form,
            'communication_methods_form' => $communication_methods_form,
            'user' => $user,
            'user_form' => $user_form,
            'communication_methods_message' => $communication_methods_message,
            'communication_methods' => $communication_methods,
        ]);
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

            $this->em->persist($clinics);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Company details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/addresses', name: 'clinic_addresses')]
    public function clinicGetAddressesAction(Request $request): Response
    {
        $data = $request->request->get('addresses_form');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $methods = $this->em->getRepository(Clinics::class)->getClinicAddresses($clinic->getId());
        $address_id = $data['address_id'];

        if($address_id == 0){

            $clinic_address = new Addresses();
            $response = '<b><i class="fas fa-check-circle"></i> Address details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $clinic_address = $this->em->getRepository(Addresses::class)->find($address_id);
            $response = '<b><i class="fas fa-check-circle"></i> Address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
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

        return new JsonResponse($response);
    }

    #[Route('/clinics/users', name: 'clinic_users')]
    public function clinicUsersAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request->get('clinic_users_form');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $user = $this->em->getRepository(ClinicUsers::class)->findBy(['email' => $data['email']]);
        $user_id = $data['user_id'];

        if(count($user) > 0){

            $response = [
                'response' => false
            ];

            return new JsonResponse($response);
        }

        if($user_id == 0){

            $clinic_user = new ClinicUsers();

            $plain_text_pwd = $this->generatePassword();

            if (!empty($plain_text_pwd)) {

                $hashed_pwd = $passwordHasher->hashPassword($clinic_user, $plain_text_pwd);

                $clinic_user->setRoles(['ROLE_USER']);
                $clinic_user->setPassword($hashed_pwd);

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $data['firstName'] .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/clinics/login">https://'. $_SERVER['HTTP_HOST'] .'/clinics/login</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $data['email'] .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plain_text_pwd .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($data['email'])
                    ->subject('Fluid Login Credentials')
                    ->html($body);

                $mailer->send($email);
            }

            $message = '<b><i class="fas fa-check-circle"></i> User details successfully created.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $clinic_user = $this->em->getRepository(ClinicUsers::class)->find($user_id);

            $clinic_user->setIsPrimary(0);

            $message = '<b><i class="fas fa-check-circle"></i> User successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $clinic_user->setClinic($clinic);
        $clinic_user->setFirstName($data['firstName']);
        $clinic_user->setLastName($data['lastName']);
        $clinic_user->setEmail($data['email']);
        $clinic_user->setTelephone($data['telephone']);
        $clinic_user->setPosition($data['position']);
        $clinic_user->setIsPrimary(0);

        $this->em->persist($clinic_user);
        $this->em->flush();

        $response = [

            'response' => true,
            'message' => $message
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/communication-methods', name: 'communication_methods')]
    public function clinicCommunicationMethodsAction(Request $request): Response
    {
        $data = $request->request->get('clinic_communication_methods_form');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $clinic_repo = $this->em->getRepository(Clinics::class)->find($clinic->getId());
        $communication_method_repo = $this->em->getRepository(CommunicationMethods::class)->find($data['communicationMethod']['clinicCommunicationMethods']);
        $get_communication_methods = $this->em->getRepository(Clinics::class)->getClinicCommunicationMethods($clinic->getId());
        $method_id = (int) $request->request->get('communication_method_id');

        if($request->request->get('communication_method_id') == 0) {

            $clinic_communication_method = new ClinicCommunicationMethods();

        } else {

            $clinic_communication_method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($method_id);
        }

        $clinic_communication_method->setClinic($clinic);
        $clinic_communication_method->setCommunicationMethod($communication_method_repo);
        $clinic_communication_method->setSendTo($data['sendTo']);
        $clinic_communication_method->setIsActive(1);

        $this->em->persist($clinic_communication_method);
        $this->em->flush();

        $response = '<b><i class="fas fa-check-circle"></i> Communication Method successfully created.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/delete', name: 'clinic_address_delete')]
    public function clinicDeleteAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $address = $this->em->getRepository(Addresses::class)->find($address_id);

        $address->setIsActive(0);

        $this->em->persist($address);
        $this->em->flush();

        $response = '<b><i class="fas fa-check-circle"></i> Address successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

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

    #[Route('/clinics/user/delete', name: 'clinic_user_delete')]
    public function clinicDeleteUser(Request $request): Response
    {
        $user_id = $request->request->get('id');
        $user = $this->em->getRepository(ClinicUsers::class)->find($user_id);

        $this->em->remove($user);
        $this->em->flush();

        $response = '<b><i class="fas fa-check-circle"></i> User successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/method/delete', name: 'clinic_method_delete')]
    public function clinicDeleteMethod(Request $request): Response
    {
        $method_id = $request->request->get('id');
        $method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($method_id);

        $method->setIsActive(0);

        $this->em->persist($method);
        $this->em->flush();

        $response = '<b><i class="fas fa-check-circle"></i> Communication method successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/default', name: 'clinic_address_default')]
    public function clinicDefaultAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $methods = $this->em->getRepository(Clinics::class)->getClinicDefaultAddresses($clinic_id, $address_id);

        $response = '<b><i class="fas fa-check-circle"></i> Default address successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

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

    #[Route('/clinics/users-refresh', name: 'clinic_refresh_users')]
    public function clinicRefreshUsersAction(Request $request): Response
    {
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $users = $this->em->getRepository(Clinics::class)->getClinicUsers($clinic_id);

        $html = '';

        foreach($users[0]->getClinicUsers() as $user){

            $html .= '<div class="list-width">
                       <div class="row t-row">
                           <div class="col-md-2 t-cell" id="string_user_first_name_'. $user->getId() .'">
                               '. $user->getFirstName() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_last_name_'. $user->getId() .'">
                               '. $user->getLastName() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_email_'. $user->getId() .'">
                               '. $user->getEmail() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_telephone_'. $user->getId() .'">
                               '. $user->getEmail() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_position_'. $user->getId() .'">
                               '. $user->getPosition() .'
                           </div>
                           <div class="col-md-2 t-cell">
                               <a href="" class="float-end" data-bs-toggle="modal" data-bs-target="#modal_user" id="user_update_{{ users.id }}">
                                   <i class="fa-solid fa-pen-to-square edit-icon"></i>
                               </a>
                               <a href="" class="delete-icon float-end" data-bs-toggle="modal"
                                  data-value="{{ users.id }}" data-bs-target="#modal_user_delete" id="user_delete_{{ users.id }}">
                                   <i class="fa-solid fa-trash-can"></i>
                               </a>
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

    #[Route('/clinics/get-user', name: 'clinic_get_user')]
    public function clinicsGetUserAction(Request $request): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($request->request->get('id'));

        $response = [

            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'position' => $user->getPosition(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/get-method', name: 'clinic_get_method')]
    public function clinicsGetMethodAction(Request $request): Response
    {

        $method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($request->request->get('id'));

        $response = [

            'id' => $method->getId(),
            'method_id' => $method->getCommunicationMethod()->getId(),
            'method' => $method->getCommunicationMethod()->getMethod(),
            'send_to' => $method->getSendTo(),
        ];

        return new JsonResponse($response);
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

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

                $response .= '
                <div class="row p-2">
                    <div class="col-8 col-sm-10 ps-1 d-flex flex-column">
                        <table style="height: 30px;">
                            <tr>
                                <td class="align-middle" width="50px">
                                    '. $icon .'
                                </td>
                                <td class="align-middle info">
                                    '. $lists[$i]->getName() .'
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-4 col-sm-2">
                        <a href="" class="float-end">View List</a>
                    </div>
                </div>
            ';
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
        $item_id = $request->request->get('id');
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

                $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
            }

            $response .= '
                <div class="row p-2">
                    <div class="col-8 col-sm-10 ps-1 d-flex flex-column">
                        <table style="height: 30px;">
                            <tr>
                                <td class="align-middle" width="50px">
                                    '. $icon .'
                                </td>
                                <td class="align-middle info">
                                    '. $lists[$i]->getName() .'
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-4 col-sm-2">
                        <a href="" class="float-end">View List</a>
                    </div>
                </div>
            ';
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

                $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
            }

            $response .= '
                <div class="row p-2">
                    <div class="col-8 col-sm-10 ps-1 d-flex flex-column">
                        <table style="height: 30px;">
                            <tr>
                                <td class="align-middle" width="50px">
                                    '. $icon .'
                                </td>
                                <td class="align-middle info">
                                    '. $lists[$i]->getName() .'
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-4 col-sm-2">
                        <a href="" class="float-end">View List</a>
                    </div>
                </div>
            ';
        }

        $response .= $this->listCreateNew($product_id);

        return new JsonResponse($response);
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

    private function noteCreateNew($product_id)
    {
        return '
            <div class="row mt-4">
                <div class="col-12">
                    <form name="form_note_'. $product_id .'" class="form_note" id="form_note_'. $product_id .'" method="post"  data-product-id="'. $product_id .'">
                        <input type="hidden" name="product_id" value="'. $product_id .'">
                        <input type="hidden" name="note_id" id="note_id_'. $product_id .'" value="0">
                        <div class="row">
                            <div class="col-12 col-sm-10 mb-3 mb-sm-0">
                                <input type="text" name="note" id="note_'. $product_id .'" class="form-control">
                                <div class="hidden_msg" id="error_note_'. $product_id .'">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-12 col-sm-2">
                                <button type="submit" class="btn btn-primary float-end w-100">
                                    <i class="fa-solid fa-circle-plus"></i>
                                    &nbsp;ADD NEW NOTE
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>';
    }

    #[Route('/clinics/inventory/get-notes', name: 'inventory_get_notes')]
    public function clinicsGetNotesAction(Request $request): Response
    {
        $product_id = (int) $request->request->get('id');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $product = $this->em->getRepository(Products::class)->find($product_id);
        $product_notes = $this->em->getRepository(ProductNotes::class)->findBy([
            'clinic' => $clinic,
            'product' => $product,
        ]);

        $response = '<h3 class="pb-3 pt-3">Item Notes</h3>';

        foreach($product_notes as $note){

            $response .= '<div class="row">
                            <div class="col-9">
                                <h6>'. $note->getNote() .'</h6>
                            </div>
                            <div class="col-3">
                                <a href="" class="float-end note_update" data-id="'. $note->getId() .'">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <a href="" class="delete-icon float-end" data-bs-toggle="modal" data-note-id="'. $note->getId() .'" data-product-id="'. $product->getId() .'" data-bs-target="#modal_note_delete" id="note_delete_'. $note->getId() .'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12 info-sm">
                                '. $note->getClinicUser()->getFirstName() .' '. $note->getClinicUser()->getLastName() .' . '. $note->getCreated()->format('M d Y H:i') .'
                            </div>
                        </div>';
        }

        $response .= $this->noteCreateNew($product_id);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/manage-note', name: 'inventory_manage_note')]
    public function clinicsManageNoteAction(Request $request): Response
    {
        $data = $request->request;
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $product = $this->em->getRepository(Products::class)->find($data->get('product_id'));
        $delete = $data->get('delete_id');
        $note_id = (int)$data->get('note_id');

        if($delete){

            $note = $this->em->getRepository(ProductNotes::class)->find($note_id);

            $this->em->remove($note);
            $this->em->flush();

        } else {

            $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
            $clinic_user = $this->em->getRepository(ClinicUsers::class)->findBy(['email' => $user_name]);

            $note_string = $data->get('note');

            // List
            if ($note_id == 0) {

                $note = new ProductNotes();

            } else {

                $note = $this->em->getRepository(ProductNotes::class)->find($note_id);
            }

            $note->setProduct($product);
            $note->setClinic($clinic);
            $note->setClinicUser($clinic_user[0]);
            $note->setNote($note_string);

            $this->em->persist($note);
            $this->em->flush();
        }

        // Get the updated list
        $product_notes = $this->em->getRepository(ProductNotes::class)->findBy([
            'clinic' => $clinic,
            'product' => $product,
        ]);
        $response = '';

        foreach($product_notes as $note){

            $response .= '<div class="row">
                            <div class="col-10">
                                <h6>'. $note->getNote() .'</h6>
                            </div>
                            <div class="col-2">
                                <a href="" class="float-end note_update" data-id="'. $note->getId() .'">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <a href="" class="delete-icon float-end" data-bs-toggle="modal" data-note-id="'. $note->getId() .'" data-product-id="'. $product->getId() .'" data-bs-target="#modal_note_delete" id="note_delete_'. $note->getId() .'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12 info-sm">
                                '. $note->getClinicUser()->getFirstName() .' '. $note->getClinicUser()->getLastName() .' . '. $note->getCreated()->format('M d Y H:i') .'
                            </div>
                        </div>';
        }

        $response .= $this->noteCreateNew($product->getId());

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/get-note', name: 'inventory_get_note')]
    public function clinicsGetNoteAction(Request $request): Response
    {
        $note = $this->em->getRepository(ProductNotes::class)->find($request->request->get('id'));

        $response = [
            'note' => $note->getNote(),
            'product_id' => $note->getProduct()->getId(),
        ];

        return new JsonResponse($response);
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
