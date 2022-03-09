<?php

namespace App\Controller;

use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    #[Route('clinics/inventory', name: 'inventory_search')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLINIC');
        $user = $this->getUser();
        $products = '';

        if($request->get('keyword') != null) {

            $products = $this->em->getRepository(Products::class)->findByKeystring($request->get('keyword'));
            dd($products);
        }

        return $this->render('frontend/inventory/inventory.html.twig', [
            'user' => $user,
        ]);
    }
}
