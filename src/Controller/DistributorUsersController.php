<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
use App\Entity\DistributorUsers;
use App\Form\ResetPasswordRequestFormType;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class DistributorUsersController extends AbstractController
{
    private $em;
    private $page_manager;
    private $mailer;

    const ITEMS_PER_PAGE = 1;

    public function __construct(EntityManagerInterface $em, PaginationManager $pagination, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->page_manager = $pagination;
        $this->mailer = $mailer;
    }

    #[Route('/distributors/get-user', name: 'distributor_get_user')]
    public function distributorGetUserAction(Request $request): Response
    {
        $user = $this->em->getRepository(DistributorUsers::class)->find($request->request->get('id'));

        $response = [

            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'position' => $user->getPosition(),
            'iso_code' => $user->getIsoCode(),
            'intl_code' => $user->getIntlCode(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/manage-users', name: 'distributor_users')]
    public function distributorUsersAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request->get('distributor_users_form');
        $distributor = $this->get('security.token_storage')->getToken()->getUser()->getDistributor();
        $user = $this->em->getRepository(DistributorUsers::class)->findBy(['email' => $data['email']]);
        $user_id = (int) $data['user_id'];

        if(count($user) > 0 && $user_id == 0){

            $response = [
                'response' => false
            ];

            return new JsonResponse($response);
        }

        if($user_id == 0){

            $distributor_user = new DistributorUsers();

            $plain_text_pwd = $this->generatePassword();

            if (!empty($plain_text_pwd)) {

                $hashed_pwd = $passwordHasher->hashPassword($distributor_user, $plain_text_pwd);

                $distributor_user->setRoles(['ROLE_DISTRIBUTOR']);
                $distributor_user->setPassword($hashed_pwd);

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $data['firstName'] .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/distributors/login">https://'. $_SERVER['HTTP_HOST'] .'/distributors/login</a></td>';
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

            $distributor_user = $this->em->getRepository(DistributorUsers::class)->find($user_id);

            $distributor_user->setIsPrimary(0);

            $message = '<b><i class="fas fa-check-circle"></i> User successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $distributor_user->setDistributor($distributor);
        $distributor_user->setFirstName($data['firstName']);
        $distributor_user->setLastName($data['lastName']);
        $distributor_user->setEmail($data['email']);
        $distributor_user->setTelephone($data['telephone']);
        $distributor_user->setIsoCode($data['isoCode']);
        $distributor_user->setIntlCode($data['intlCode']);
        $distributor_user->setPosition($data['position']);
        $distributor_user->setIsPrimary(0);

        $this->em->persist($distributor_user);
        $this->em->flush();

        $response = [

            'response' => true,
            'message' => $message
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/users-refresh', name: 'distributor_refresh_users')]
    public function distributorRefreshUsersAction(Request $request): Response
    {
        $distributor_id = $this->get('security.token_storage')->getToken()->getUser()->getDistributor()->getId();
        $users = $this->em->getRepository(DistributorUsers::class)->findDistributorUsers($distributor_id);
        $user_results = $this->page_manager->paginate($users[0], $request, self::ITEMS_PER_PAGE);
        $page_id = $request->request->get('page_id');
        $html = '';

        foreach($user_results as $user){

            $html .= '
            <div class="list-width">
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
                       <a href="" class="float-end update-user" data-bs-toggle="modal" data-bs-target="#modal_user" data-user-id="'. $user->getId() .'">
                           <i class="fa-solid fa-pen-to-square edit-icon"></i>
                       </a>
                       <a href="" class="delete-icon float-end delete-user" data-bs-toggle="modal"
                          data-value="'. $user->getId() .'" data-bs-target="#modal_user_delete" data-user-id="'. $user->getId() .'">
                           <i class="fa-solid fa-trash-can"></i>
                       </a>
                   </div>
               </div>
           </div>';
        }

        $pagination = $this->getPagination($page_id, $user_results, $distributor_id);

        $html .= $pagination;

        return new JsonResponse($html);
    }

    #[Route('/distributors/user/delete', name: 'distributor_user_delete')]
    public function distributorDeleteUser(Request $request): Response
    {
        $user_id = (int) $request->request->get('id');
        $user = $this->em->getRepository(DistributorUsers::class)->find($user_id);

        $this->em->remove($user);
        $this->em->flush();

        $response = '<b><i class="fas fa-check-circle"></i> User successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/distributors/forgot-password', name: 'distributors_forgot_password_request')]
    public function clinicForgotPasswordAction(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $distributor_user = $this->em->getRepository(DistributorUsers::class)->findOneBy(
                [
                    'email' => $request->request->get('reset_password_request_form')['email']
                ]
            );

            if($distributor_user != null){

                $resetToken = uniqid();

                $distributor_user->setResetKey($resetToken);

                $this->em->persist($distributor_user);
                $this->em->flush();

                $html = '
                To reset your password, please visit the following link
                <br><br>
                https://'. $_SERVER['HTTP_HOST'] .'/distributors/reset/'. $resetToken;

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($distributor_user->getEmail())
                    ->subject('Fluid Password Reset')
                    ->html($html);

                $this->mailer->send($email);

                return $this->render('reset_password/distributors_check_email.html.twig');
            }
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/distributors/reset/{token}', name: 'distributors_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, string $token = null, MailerInterface $mailer): Response
    {
        $plain_text_pwd = $this->generatePassword();
        $distributor_user = $this->em->getRepository(DistributorUsers::class)->findOneBy([
            'resetKey' => $request->get('token')
        ]);

        if (!empty($plain_text_pwd)) {

            $hashed_pwd = $passwordHasher->hashPassword($distributor_user, $plain_text_pwd);

            $distributor_user->setPassword($hashed_pwd);

            $this->em->persist($distributor_user);
            $this->em->flush();

            // Send Email
            $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
            $body .= '<tr><td colspan="2">Hi '. $distributor_user->getFirstName() .',</td></tr>';
            $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
            $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
            $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
            $body .= '<tr>';
            $body .= '    <td><b>URL: </b></td>';
            $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/distributors/login">https://'. $_SERVER['HTTP_HOST'] .'/distributors/login</a></td>';
            $body .= '</tr>';
            $body .= '<tr>';
            $body .= '    <td><b>Username: </b></td>';
            $body .= '    <td>'. $distributor_user->getEmail() .'</td>';
            $body .= '</tr>';
            $body .= '<tr>';
            $body .= '    <td><b>Password: </b></td>';
            $body .= '    <td>'. $plain_text_pwd .'</td>';
            $body .= '</tr>';
            $body .= '</table>';

            $email = (new Email())
                ->from($this->getParameter('app.email_from'))
                ->addTo($distributor_user->getEmail())
                ->subject('Fluid Login Credentials')
                ->html($body);

            $mailer->send($email);
        }

        return $this->redirectToRoute('distributors_password_reset');
    }

    #[Route('/distributors/password/reset', name: 'distributors_password_reset')]
    public function distributorPasswordReset(Request $request): Response
    {
        return $this->render('reset_password/distributors_password_reset.html.twig');
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

    private function sendLoginCredentials($clinic_user, $plain_text_pwd, $data)
    {

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

        $this->mailer->send($email);
    }

    public function getPagination($page_id, $results, $distributor_id)
    {
        $current_page = (int) $page_id;
        $last_page = $this->page_manager->lastPage($results);

        $pagination = '
        <!-- Pagination -->
        <div class="row mt-3">
            <div class="col-12">';

        if($last_page > 1) {

            $previous_page_no = $current_page - 1;
            $url = '/distributors/users';
            $previous_page = $url . $previous_page_no;

            $pagination .= '
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

            $pagination .= '
            <li class="page-item '. $disabled .'">
                <a 
                    class="user-pagination" 
                    aria-disabled="'. $data_disabled .'" 
                    data-page-id="'. $current_page - 1 .'" 
                    data-distributor-id="'. $distributor_id .'"
                    href="'. $previous_page .'"
                >
                    <span aria-hidden="true">&laquo;</span> Previous
                </a>
            </li>';

            for($i = 1; $i <= $last_page; $i++) {

                $active = '';

                if($i == (int) $current_page){

                    $active = 'active';
                }

                $pagination .= '
                <li class="page-item '. $active .'">
                    <a 
                        class="user-pagination" 
                        data-page-id="'. $i .'" 
                        href="'. $url .'"
                        data-distributor-id="'. $distributor_id .'"
                    >'. $i .'</a>
                </li>';
            }

            $disabled = 'disabled';
            $data_disabled = 'true';

            if($current_page < $last_page) {

                $disabled = '';
                $data_disabled = 'false';
            }

            $pagination .= '
            <li class="page-item '. $disabled .'">
                <a 
                    class="user-pagination" 
                    aria-disabled="'. $data_disabled .'" 
                    data-page-id="'. $current_page + 1 .'" 
                    href="'. $url .'"
                    data-distributor-id="'. $distributor_id .'"
                >
                    Next <span aria-hidden="true">&raquo;</span>
                </a>
            </li>';

            $pagination .= '
                    </ul>
                </nav>';

            $pagination .= '
                </div>
            </div>';
        }

        return $pagination;
    }
}
