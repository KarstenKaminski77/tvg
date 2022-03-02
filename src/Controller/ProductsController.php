<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    #[Route('clinics/inventory', name: 'inventory_search')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLINIC');
        $user = $this->getUser();

        return $this->render('frontend/inventory/inventory.html.twig', [
            'user' => $user,
        ]);
    }
}
