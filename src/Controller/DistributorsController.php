<?php

namespace App\Controller;

use App\Entity\Distributors;
use App\Form\DistributorFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class DistributorsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/distributors', name: 'distributors')]
    public function index(): Response
    {
        return $this->render('distributors/index.html.twig', [
            'controller_name' => 'DistributorsController',
        ]);
    }

    #[Route('/distributor/register', name: 'distributor_reg')]
    public function distributorReg(Request $request): Response
    {
        $form = $this->createRegisterForm();

        return $this->render('frontend/distributors/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function createRegisterForm()
    {
        $distributors = new Distributors();

        return $this->createForm(DistributorFormType::class, $distributors);
    }

    #[Route('/distributor/register/create', name: 'distributor_create')]
    public function distributorCreateAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request;
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $data->get('email')]);

        if($distributor == null) {

            $distributor = new Distributors();

            $plain_text_pwd = $this->generatePassword();
            $hashed_pwd = $passwordHasher->hashPassword($distributor, $plain_text_pwd);

            if (!empty($plain_text_pwd)) {

                $distributor->setDistributorName($data->get('distributor_name'));
                $distributor->setFirstName($data->get('first_name'));
                $distributor->setLastName($data->get('last_name'));
                $distributor->setEmail($data->get('email'));
                $distributor->setTelephone($data->get('telephone'));
                $distributor->setRoles(['ROLE_USER']);
                $distributor->setPassword($hashed_pwd);

                $this->em->persist($distributor);
                $this->em->flush();

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $distributor->getFirstName() .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the TVG Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/distributor/login">https://'. $_SERVER['HTTP_HOST'] .'/distributor/login</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $distributor->getUsername() .'</td>';
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
