<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Categories;
use App\Entity\Clinics;
use App\Entity\CommunicationMethods;
use App\Entity\Products;
use App\Entity\Species;
use App\Entity\SubCategories;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        // Option 1. Make your dashboard redirect to the same page for all users
        return $this->redirect($adminUrlGenerator->setController(ProductsCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setFaviconPath('images/favicon.ico')
            ->setTitle('<img src="/images/logo.png" style="width: 150px !important; margin: auto" align="center">');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home')
            ->setPermission('ROLE_PRODUCT');
        yield MenuItem::linkToCrud('Categories', 'fas fa-bars', Categories::class)
            ->setPermission('ROLE_CATEGORY');
        yield MenuItem::linkToCrud('Clinics', 'fas fa-clinic-medical', Clinics::class)
            ->setPermission('ROLE_CLINIC');
        yield MenuItem::linkToCrud('Communication Methods', 'fas fa-broadcast-tower', CommunicationMethods::class)
            ->setPermission('ROLE_COMMUNICATION_METHOD');
        yield MenuItem::linkToCrud('Products', 'fab fa-product-hunt', Products::class)
            ->setPermission('ROLE_PRODUCT');
        yield MenuItem::linkToCrud('Species', 'fas fa-paw', Species::class)
            ->setPermission('ROLE_SPECIE');
        yield MenuItem::linkToCrud('Sub Categories', 'fas fa-list', SubCategories::class)
            ->setPermission('ROLE_SUB_CATEGORY');
        yield MenuItem::linkToCrud('Users', 'fas fa-user', User::class)
            ->setPermission('ROLE_USER');
    }
}