<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\ClinicCommunicationMethods;
use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
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
        $addresses = new Addresses();

        return $this->createForm(AddressesFormType::class, $addresses);
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
                $body .= '<tr><td colspan="2">Please use the credentials below login to the TVG Backend.</td></tr>';
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
                    ->subject('TVG Login Credentials')
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

        $clinic_email = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getEmail();
        $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $user = $this->em->getRepository(ClinicUsers::class)->findOneBy(['email' => $user_name]);
        $clinics = $this->em->getRepository(Clinics::class)->findOneBy(['email' => $clinic_email]);
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

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
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

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/addresses', name: 'clinic_addresses')]
    public function clinicGetAddressesAction(Request $request): Response
    {
        $data = $request->request->get('addresses_form');
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $addresses = $this->em->getRepository(Clinics::class)->getClinicAddresses($clinic_id);
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $user = $this->em->getRepository(ClinicUsers::class)->findOneBy(['email' => $username]);
        $address_id = $data['address_id'];

        if($address_id == 0){

            $clinic_address = new Addresses();
            $response = '<b><i class="fas fa-check-circle"></i> Address details successfully updated.';

        } else {

            $clinic_address = $this->em->getRepository(Addresses::class)->find($address_id);
            $response = '<b><i class="fas fa-check-circle"></i> Address successfully updated.';
        }

        $clinic_address->setClinic($user->getClinic());
        $clinic_address->setClinicName($data['clinicName']);
        $clinic_address->setTelephone($data['telephone']);
        $clinic_address->setAddress($data['address']);
        $clinic_address->setSuite($data['suite']);
        $clinic_address->setPostalCode($data['postalCode']);
        $clinic_address->setCity($data['city']);
        $clinic_address->setState($data['state']);
        $clinic_address->setIsDefault(0);
        $clinic_address->setIsActive(1);

        if($addresses[0]->getAddresses()[0]->getAddress() == null){

            $clinic_address->setIsDefault(1);
        }

        $this->em->persist($clinic_address);
        $this->em->flush();

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

        $response = '<b><i class="fas fa-check-circle"></i> Address successfully deleted.';

        return new JsonResponse($response);
    }

    #[Route('/clinics/address/default', name: 'clinic_address_default')]
    public function clinicDefaultAddress(Request $request): Response
    {
        $address_id = $request->request->get('id');
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $addresses = $this->em->getRepository(Clinics::class)->getClinicDefaultAddresses($clinic_id, $address_id);

        $response = '<b><i class="fas fa-check-circle"></i> Default address successfully updated.';

        return new JsonResponse($response);
    }

    #[Route('/clinics/addresse-refresh', name: 'clinic_refresh_addresses')]
    public function clinicRefreshAddressesAction(Request $request): Response
    {
        $clinic_id = $this->get('security.token_storage')->getToken()->getUser()->getClinic()->getId();
        $addresses = $this->em->getRepository(Clinics::class)->getClinicAddresses($clinic_id);

        $html = '';

        foreach($addresses[0]->getAddresses() as $address){

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
