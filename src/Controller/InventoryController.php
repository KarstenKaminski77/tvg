<?php

namespace App\Controller;

use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\Lists;
use App\Entity\ProductNotes;
use App\Entity\Products;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InventoryController extends AbstractController
{
    const ITEMS_PER_PAGE = 12;
    private $page_manager;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, PaginationManager $pagination)
    {
        $this->page_manager = $pagination;
        $this->em = $entityManager;
    }

    #[Route('/clinics/inventory', name: 'frontend')]
    public function index(Request $request): Response
    {
        $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $products = '';

        //if($request->get('keyword') != null) {

            $products = $this->em->getRepository(Products::class)->findByKeystring('Enrofloxacin Flavor Tablets');
            //$results = $this->pageManager->paginate($auction_vehicles, $request, self::ITEMS_PER_PAGE);
        //}

        return $this->render('frontend/inventory/inventory.html.twig',[
            'user' => $user,
            'products' => $products,
        ]);
    }
}
