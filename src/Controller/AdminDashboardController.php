<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Manufacturers;
use App\Entity\Products;
use App\Entity\Species;
use App\Entity\SubCategories;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    private $em;
    private $page_manager;
    const ITEMS_PER_PAGE = 10;

    public function __construct(EntityManagerInterface $em, PaginationManager $page_manager)
    {
        $this->em = $em;
        $this->page_manager = $page_manager;
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('Admin/dashboard.html.twig');
    }

    #[Route('/admin/products/{page_id}', name: 'products_list')]
    public function productsList(Request $request): Response
    {
        $products = $this->em->getRepository(Products::class)->adminFindAll();
        $results = $this->page_manager->paginate($products[0], $request, self::ITEMS_PER_PAGE);

        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/products/');

        return $this->render('Admin/products_list.html.twig',[
            'products' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/product/{product_id}', name: 'products')]
    public function productsCrud(Request $request): Response
    {
        $product = $this->em->getRepository(Products::class)->find($request->get('product_id'));
        $manufacturers = $this->em->getRepository(Manufacturers::class)->findAll();
        $species = $this->em->getRepository(Species::class)->findAll();
        $categories = $this->em->getRepository(Categories::class)->findAll();
        $sub_categories = $this->em->getRepository(SubCategories::class)->findAll();

        return $this->render('Admin/products.html.twig',[
            'product' => $product,
            'manufacturers' => $manufacturers,
            'species' => $species,
            'categories' => $categories,
            'sub_categories' => $sub_categories,
        ]);
    }

    public function getPagination($page_id, $results, $url)
    {
        $current_page = $page_id;
        $last_page = $this->page_manager->lastPage($results);
        $pagination = '';

        if(count($results) > 0) {

            $pagination .= '
            <!-- Pagination -->
            <div class="row">
                <div class="col-12">';

            if ($last_page > 1) {

                $previous_page_no = $current_page - 1;
                $previous_page = $url . $previous_page_no;

                $pagination .= '
                <nav class="custom-pagination">
                    <ul class="pagination justify-content-center">
                ';

                $disabled = 'disabled';
                $data_disabled = 'true';

                // Previous Link
                if ($current_page > 1) {

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $pagination .= '
                <li class="page-item ' . $disabled . '">
                    <a 
                        class="address-pagination" 
                        href="' . $previous_page . '"
                    >
                        <span aria-hidden="true">&laquo;</span> <span class="d-none d-sm-inline">Previous</span>
                    </a>
                </li>';

                $is_active = false;

                for ($i = 1; $i <= $last_page; $i++) {

                    $active = '';

                    if ($i == (int)$current_page) {

                        $active = 'active';
                        $is_active = true;
                    }

                    // Go to previous page if all records for a page have been deleted
                    if(!$is_active && $i == count($results)){

                        $active = 'active';
                    }

                    $pagination .= '
                    <li class="page-item ' . $active . '">
                        <a class="address-pagination" href="' . $url . $i . '">' . $i . '</a>
                    </li>';
                }

                $disabled = 'disabled';
                $data_disabled = 'true';

                if ($current_page < $last_page) {

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $pagination .= '
                <li class="page-item ' . $disabled . '">
                    <a 
                        class="address-pagination" 
                        aria-disabled="' . $data_disabled . '" 
                        href="' . $url . $current_page + 1 . '">
                        <span class="d-none d-sm-inline">Next</span> <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>';

                if(count($results) < $current_page){

                    $current_page = count($results);
                }

                $pagination .= '
                        </ul>
                    </nav>
                </div>';
            }
        }

        return $pagination;
    }
}