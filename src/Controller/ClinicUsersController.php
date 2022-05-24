<?php

namespace App\Controller;

use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ClinicUsersController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/clinics/get-clinic-users', name: 'get-clinic-users')]
    public function getClinicUsersAction(): Response
    {
        $clinic = $this->getUser()->getClinic();
        $clinic_users = $this->em->getRepository(ClinicUsers::class)->findBy([
            'clinic' => $clinic->getId()
        ]);
        
        $response = '
        <!-- Users -->
        <div class="row" id="users">
            <div class="col-12 col-md-12 mb-3 mt-0 pe-0">
                <!-- Create New -->
                <button type="button" class="btn btn-primary float-end w-sm-100" data-bs-toggle="modal" data-bs-target="#modal_user" id="user_new">
                    <i class="fa-solid fa-circle-plus"></i> ADD COLLEAGUE
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3 mt-4" id="order_header">
                <h3 class="text-light">Manage User Accounts</h3>
                <span class="mb-5 mt-2 text-center text-light text-sm-start">
                    Fluid supports having several users under a single clinic. Each user will have their own login, can
                    independently participate in the Fluid discussions. You have full control over editing the permissions
                    of each user in your clinic. Use the table below to view the available permission levels.
                </span>
            </div>
        </div>

        <div class="row d-none d-xl-flex bg-light border-bottom border-right border-left">
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                First Name
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Last Name
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Username
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Telephone
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Position
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">

            </div>
        </div>';

        foreach($clinic_users as $user) {

            $response .= '
           <div class="row bg-light border-bottom border-right border-left">
               <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">First Name</div>
               <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                   '. $user->getFirstName() .'
               </div>
               <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Last Name</div>
               <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                   '. $user->getLastName() .'
               </div>
               <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Email</div>
               <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                   '. $user->getEmail() .'
               </div>
               <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Telephone</div>
               <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                   '. $user->getTelephone() .'
               </div>
               <div class="col-4 col-md-2 d-xl-none t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3">Position</div>
               <div class="col-8 col-md-10 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                   '. $user->getPosition() .'
               </div>
               <div class="col-12 col-xl-2 t-cell text-truncate border-list pt-3 pb-3">
                   <a href="" class="float-end open-user-modal" data-bs-toggle="modal" data-bs-target="#modal_user" data-user-id="'. $user->getId() .'">
                       <i class="fa-solid fa-pen-to-square edit-icon"></i>
                   </a>
                   <a href="" class="delete-icon float-start float-sm-end open-delete-user-modal" data-bs-toggle="modal"
                      data-value="'. $user->getId() .'" data-bs-target="#modal_user_delete" data-user-id="'. $user->getId() .'">
                       <i class="fa-solid fa-trash-can"></i>
                   </a>
               </div>
           </div>';
        }

        $response .= '

            <!-- Modal Manage Users -->
            <div class="modal fade" id="modal_user" tabindex="-1" aria-labelledby="modal_user" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form name="form_users" id="form_users" method="post">
                            <div class="modal-header">
                                <h5 class="modal-title" id="user_modal_label"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">

                                    <!-- First Name -->
                                    <div class="col-12 col-sm-6">
                                        <label>First Name</label>
                                        <input type="hidden" value="" name="clinic_users_form[user_id]" id="user_id">
                                        <input 
                                            type="text" 
                                            name="clinic_users_form[firstName]" 
                                            id="user_first_name" 
                                            placeholder="First Name*"
                                            class="form-control"
                                            value=""
                                        >
                                        <div class="hidden_msg" id="error_user_first_name">
                                            Required Field
                                        </div>
                                    </div>

                                    <!-- Last Name -->
                                    <div class="col-12 col-sm-6">
                                        <label>Last Name</label>
                                        <input 
                                            type="text" 
                                            name="clinic_users_form[lastName]" 
                                            id="user_last_name" 
                                            placeholder="Last Name*"
                                            class="form-control"
                                            value=""
                                        >
                                        <div class="hidden_msg" id="error_user_last_name">
                                            Required Field
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">

                                    <!-- Email -->
                                    <div class="col-12 col-sm-6">
                                        <label>Email</label>
                                        <input 
                                            type="text" 
                                            name="clinic_users_form[email]" 
                                            id="user_email" 
                                            placeholder="Email Address*"
                                            class="form-control"
                                            value=""
                                        >
                                        <div class="hidden_msg" id="error_user_email">
                                            Required Field
                                        </div>
                                    </div>

                                    <!-- Telephone Number -->
                                    <div class="col-12 col-sm-6">
                                        <label>Telepgone</label>
                                        <input 
                                            type="text" 
                                            name="clinic_users_form[telephone]" 
                                            id="user_telephone" 
                                            placeholder="(123) 456-7890*"
                                            class="form-control"
                                            value=""
                                        >
                                        <div class="hidden_msg" id="error_user_telephone">
                                            Required Field
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">

                                    <!-- Position -->
                                    <div class="col-12">
                                        <label>Position</label>
                                        <input 
                                            type="text" 
                                            name="clinic_users_form[position]" 
                                            id="user_position" 
                                            placeholder="Position"
                                            class="form-control"
                                            value=""
                                        >
                                        <div class="hidden_msg" id="error_user_telephone">
                                            Required Field
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="btn btn-primary" id="create_user">SAVE</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Delete User -->
            <div class="modal fade" id="modal_user_delete" tabindex="-1" aria-labelledby="user_delete_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="user_delete_label">Delete User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 mb-0">
                                    Are you sure you would like to delete this user? This action cannot be undone.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">CANCEL</button>
                            <button type="submit" class="btn btn-danger btn-sm" id="delete_user">DELETE</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Users -->';
        
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

    #[Route('/clinics/users-refresh', name: 'clinic_refresh_users')]
    public function clinicRefreshUsersAction(Request $request): Response
    {
        $clinic_id = $this->getUser()->getClinic()->getId();
        $users = $this->em->getRepository(Clinics::class)->getClinicUsers($clinic_id);

        $html = '
        <div class="row" id="users">
            <div class="col-12 col-md-12 mb-3 mt-0">
                <!-- Create New -->
                <button type="button" class="btn btn-primary float-end w-sm-100" data-bs-toggle="modal" data-bs-target="#modal_user" id="user_new">
                    <i class="fa-solid fa-circle-plus"></i> ADD COLLEAGUE
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3 mt-4" id="order_header">
                <h3 class="text-light">Manage User Accounts</h3>
                <span class="mb-5 mt-2 text-center text-light text-sm-start">
                    Fluid supports having several users under a single clinic. Each user will have their own login, 
                    can independently participate in the Fluid discussions. You have full control over editing the 
                    permissions of each user in your clinic. Use the table below to view the available permission levels.
                </span>
            </div>
        </div>
        <div class="row d-none d-xl-flex bg-light border-bottom border-right border-left">
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                First Name
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Last Name
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Username
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Telephone
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">
                Position
            </div>
            <div class="col-md-2 pt-3 pb-3 text-primary fw-bold">

            </div>
        </div>';

        foreach($users[0]->getClinicUsers() as $user){

            $html .= '
            <div>
               <div class="row bg-light border-left border-bottom">
                   <div class="col-md-2 t-cell fw-bold text-primary text-truncate border-list pt-3 pb-3" id="string_user_first_name_'. $user->getId() .'">
                       '. $user->getFirstName() .'
                   </div>
                   <div class="col-md-2 t-cell text-primary text-truncate border-list pt-3 pb-3" id="string_user_last_name_'. $user->getId() .'">
                       '. $user->getLastName() .'
                   </div>
                   <div class="col-md-2 t-cell text-primary text-truncate border-list pt-3 pb-3" id="string_user_email_'. $user->getId() .'">
                       '. $user->getEmail() .'
                   </div>
                   <div class="col-md-2 t-cell text-primary text-truncate border-list pt-3 pb-3" id="string_user_telephone_'. $user->getId() .'">
                       '. $user->getTelephone() .'
                   </div>
                   <div class="col-md-2 t-cell text-primary text-truncate border-list pt-3 pb-3" id="string_user_position_'. $user->getId() .'">
                       '. $user->getPosition() .'
                   </div>
                   <div class="col-md-2 t-cell text-primary border-list pt-3 pb-3">
                       <a href="" class="float-end" data-bs-toggle="modal" data-bs-target="#modal_user" id="user_update_'. $user->getId() .'">
                           <i class="fa-solid fa-pen-to-square edit-icon"></i>
                       </a>
                       <a href="" class="delete-icon float-end open-delete-user-modal" data-bs-toggle="modal"
                          data-user-id="'. $user->getId() .'" data-bs-target="#modal_user_delete">
                           <i class="fa-solid fa-trash-can"></i>
                       </a>
                   </div>
               </div>
           </div>';
        }

        return new JsonResponse($html);
    }

    #[Route('/clinics/get-users', name: 'clinic_get_users')]
    public function clinicUsersAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request->get('clinic_users_form');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $user = $this->em->getRepository(ClinicUsers::class)->findBy(['email' => $data['email']]);
        $user_id = $data['user_id'];

        if($user_id == 0){

            if(count($user) > 0){

                $response = [
                    'response' => false
                ];

                return new JsonResponse($response);
            }

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
