<?php

namespace App\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/admin/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Request$request, AuthorizationChecker $checker): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token_intention' => 'authenticate',
            'user_type' => '',

        ]);
    }

    /**
     * @Route("/distributors/login", name="distributor_login")
     */
    public function daistributorLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if (true === $checker->isGranted('ROLE_DISTRIBUTOR')) {

            $distributor_id = $this->getUser()->getDistributor()->getId();

            header('Location: '. $this->getParameter('app.base_url') . '/distributors/orders/' . $distributor_id);
//            $this->redirectToRoute('clinic_orders_list',[
//                'clinic_id' => $clinic_id
//            ]);

            die();
        }
        $uri = explode('/', $request->getPathInfo());

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token_intention' => 'authenticate',
            'user_type' => $uri[1],

        ]);
    }

    /**
     * @Route("/clinics/login", name="clinics_login")
     */
    public function clinicLogin(AuthenticationUtils $authenticationUtils, Request $request, AuthorizationChecker $checker): Response
    {
        if (true === $checker->isGranted('ROLE_CLINIC')) {

            $clinic_id = $this->getUser()->getClinic()->getId();

            header('Location: '. $this->getParameter('app.base_url') . '/clinics/orders/' . $clinic_id);
//            $this->redirectToRoute('clinic_orders_list',[
//                'clinic_id' => $clinic_id
//            ]);

            die();
        }

        $uri = explode('/', $request->getPathInfo());

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token_intention' => 'authenticate',
            'user_type' => $uri[1],

        ]);
    }

    /**
     * @Route("/admin/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/clinics/logout", name="clinics_logout")
     */
    public function clinicLogout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/distributors/logout", name="distributors_logout")
     */
    public function distributorLogout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}