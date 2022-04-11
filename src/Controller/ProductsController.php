<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
use App\Entity\ListItems;
use App\Entity\OrderItems;
use App\Entity\ProductFavourites;
use App\Entity\ProductNotes;
use App\Entity\Products;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;
    private $page_manager;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, PaginationManager $pagination)
    {
        $this->page_manager = $pagination;
        $this->em = $entityManager;
    }

    #[Route('/clinics/inventory', name: 'search_results')]
    #[Route('/clinics/dashboard', name: 'clinic_dashboard')]
    public function index(Request $request): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $response = 'Please use the search bar above....';

        return $this->render('frontend/inventory/inventory.html.twig',[
            'user' => $user,
            'response' => $response,
        ]);
    }

    #[Route('/clinics/search-inventory', name: 'search_inventory')]
    public function getSearchInventoryAction(Request $request, int $page_no = 1): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $response = '';
        $list_id = '';

        if($request->get('keyword') != null || $request->get('list_id') != null) {

            if($request->get('keyword') != null) {

                $products = $this->em->getRepository(Products::class)->findByKeystring($request->get('keyword'));

            } elseif($request->get('list_id') != null){

                $list_items = $this->em->getRepository(ListItems::class)->findBy([
                    'list' => $request->get('list_id')
                ]);

                $list_id = 'data-list-id="'. $request->get('list_id') .'"';
                $product_ids = [];

                foreach($list_items as $item){

                    $product_ids[] = $item->getProduct()->getId();
                }

                $products = $this->em->getRepository(Products::class)->findByListId($product_ids);
            }

            $results = $this->page_manager->paginate($products, $request, self::ITEMS_PER_PAGE);

            foreach($results->getQuery()->getResult() as $product){

                $product_notes = $this->em->getRepository(ProductNotes::class)->findNotes($product->getId(), $user->getClinic()->getId());
                $count_reviews = $product->getProductReviews()->count();
                $count_notes = $product->getProductNotes()->count();
                $count_clinics_bought = $this->em->getRepository(OrderItems::class)->findBy([
                    'product' => $product->getId()
                ]);
                $product_favourite = $this->em->getRepository(ProductFavourites::class)->findOneBy([
                    'product' => $product->getId(),
                    'clinic' => $this->getUser()->getClinic()->getId()
                ]);
                $product_manufacturers = $product->getProductManufacturers();

                if(count($product_manufacturers) == 1){

                    $manufacturer = $product_manufacturers[0]->getManufacturers()->getName();

                } else {

                    $manufacturer = 'Multiple Manufacturers';
                }

                $note = '';
                $class = '';
                $review_count = '';
                $note_count = '';

                if($count_reviews > 0){

                    $review_count = '
                    <span 
                        class="position-absolute text-opacity-25 start-100 translate-middle badge border rounded-circle bg-primary"
                        style="z-index: 999"
                    >
                        '. $count_reviews .'
                    </span>';
                }

                if($count_notes > 0){

                    $note_count = '
                    <span 
                        class="position-absolute text-opacity-25 start-100 translate-middle badge border rounded-circle bg-primary"
                        style="z-index: 999"
                    >
                        '. $count_notes .'
                    </span>';
                }

                if($product_favourite == null){

                    $favourite_icon = 'icon-unchecked';

                } else {

                    $favourite_icon = 'text-secondary';
                }

                if($product_notes == null){

                    $class = 'hidden_msg';
                    $first_name = '';
                    $last_name = '';
                    $note_string = '';

                } else {

                    $first_name = $product_notes[0]->getClinicUser()->getFirstName();
                    $last_name = $product_notes[0]->getClinicUser()->getLastName();
                    $note_string = $product_notes[0]->getNote();
                    $note = '<i class="fa-solid fa-pen-to-square"></i> <b>Notes From '. $first_name .' '. $last_name .':</b> '. $note_string;
                }

                $per = strtolower($product->getForm());
                $name = $product->getName() .': ';
                $dosage = $product->getDosage() . $product->getUnit() .', '. $product->getSize() .' Count';
                $price = number_format($product->getUnitPrice() / $product->getSize(), 3);
                $pieces = explode('.', $price);

                if(substr($pieces[1], 2,1) == 0){

                    $pieces[1] = substr($pieces[1],0,2);
                }

                $price = $pieces[0] .'.'. $pieces[1];

                if($product->getForm() == 'Each'){

                    $per = strtolower($product->getUnit());
                    $dosage = $product->getSize() . $product->getUnit();
                }

                $from = '</span>From $'. $price .' / '. $per;

                $response .= '<div class="row mb-4 prd-container p-0 ms-1 ms-sm-0 me-1 me-sm-0">';

                $response .= '
                <div class="alert-warning p-2 '. $class .'" id="product_notes_label_'. $product->getId() .'">'. $note .'</div>
                <!-- Product main container -->
                <div class="col-12 col-sm-9 ps-3 text-center text-sm-start">
                    <div class="row">
                        <!-- Thumbnail -->
                        <div class="col-12 col-sm-2 pt-3 text-center position-relative">
                            <img src="/images/products/'. $product->getImage() .'" class="img-fluid prd-img">
                            <a 
                                href="" 
                                class="favourite '. $favourite_icon .'"
                                data-product-id="'. $product->getId() .'"
                                id="favourite_'. $product->getId() .'"
                            >
                                <i class="fa-solid fa-heart"></i>
                            </a>
                        </div>
                        <!-- Description -->
                        <div class="col-12 col-sm-10 pt-3 pb-3">
                           <h4>'. $name . $dosage .'</h4>
                           <p><span class="pe-2">'. $manufacturer . $from .'</p>
                            <!-- Product rating -->
                            <div id="parent_'. $product->getId() .'" class="mb-3 mt-2 d-inline-block">
                                <i class="star star-under fa fa-star">
                                    <i class="star star-over fa fa-star"></i>
                                </i>
                                <i class="star star-under fa fa-star">
                                    <i class="star star-over fa fa-star"></i>
                                </i>
                                <i class="star star-under fa fa-star">
                                    <i class="star star-over fa fa-star"></i>
                                </i>
                                <i class="star star-under fa fa-star">
                                    <i class="star star-over fa fa-star"></i>
                                </i>
                                <i class="star star-under fa fa-star">
                                    <i class="star star-over fa fa-star"></i>
                                </i>
                            </div>
                            '. $this->forward('App\Controller\ProductReviewsController::getReviewsOnLoadAction', [
                                'product_id' => $product->getId()
                            ])->getContent() .'
                        </div>

                        <!-- Collapsable panel buttons -->
                        <div class="col-12 search-panels-header">
                            <!-- Description -->
                            <button class="btn btn-sm btn-light info ps-0 pe-4 pe-sm-0 btn_details" type="button" data-product-id="'. $product->getId() .'">
                                <i class="fa-regular fa-circle-question"></i> <span class="d-none d-sm-inline">Details</span>
                            </button>
                            <!-- Shopping lists -->
                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_lists" type="button" data-product-id="'. $product->getId() .'">
                                <i class="fa-solid fa-list"></i> <span class="d-none d-sm-inline">Lists</span>
                            </button>
                            <!-- Tracking -->
                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_track" type="button" data-product-id="'. $product->getId() .'">
                                <i class="fa-regular fa-eye"></i> <span class="d-none d-sm-inline">Track</span>
                            </button>
                            <!-- Notes -->
                            <button 
                                class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_notes position-relative" 
                                type="button" 
                                data-product-id="'. $product->getId() .'"
                                id="btn_note_'. $product->getId() .'"
                            >
                                <i class="fa-solid fa-pencil"></i> <span class="d-none d-sm-inline">Notes</span>
                                '. $note_count .'
                            </button>
                            <!-- Reviews -->
                            <button 
                                class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_reviews position-relative" 
                                type="button" 
                                data-product-id="'. $product->getId() .'"
                            >
                                <i class="fa-regular fa-star"></i> <span class="d-none d-sm-inline">Reviews</span>
                                '. $review_count .'
                            </button>
                            <div class="d-inline-block float-end text-end text-secondary">
                                <span 
                                    data-bs-trigger="hover"
                                    data-bs-container="body" 
                                    data-bs-toggle="popover" 
                                    data-bs-placement="top" 
                                    data-bs-html="true"
                                    data-bs-content="<b>'. count($count_clinics_bought) .'</b> clinics have recently purchased this item"
                                >
                                    <i class="fa-solid fa-chart-column text-secondary me-2"></i>'. count($count_clinics_bought) .'
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distributors -->
                <div class="col-12 col-sm-3 mt-0 pt-3 pe-4 search-result-distributors">';

                foreach($product->getDistributorProducts() as $distributor) {
                    $product_id = $product->getId();
                    $distributor_id = $distributor->getDistributor()->getId();

                    $stock_icon = 'in-stock';
                    $disabled = '';
                    $stock_copy = '<span class="is_available">In Stock</span> This item is in stock and ready to ship';
                    $in_stock = true;
                    $btn_disabled = '';

                    if($distributor->getStockCount() == 0){

                        $stock_icon = 'out-of-stock';
                        $disabled = 'disabled';
                        $stock_copy = '<span class="not_available">Out Of Stock</span> This item is out of stock';
                        $in_stock = false;
                        $btn_disabled = 'btn-secondary disabled';
                    }

                    $response .= '
                    <a href=""
                       class="basket_link"
                       data-product-id="' . $product->getId() . '"
                       data-distributor-id="' . $distributor_id . '"
                       data-bs-toggle="modal"
                       data-bs-target="#modal_add_to_basket_' . $product_id . '_' . $distributor_id . '"
                    >
                        <div class="row distributor-store-row">
                            <div class="col-4">
                                <img src="/images/logos/' . $distributor->getDistributor()->getLogo() . '" class="img-fluid mh-30">
                            </div>
                            <div class="col-4 text-center">
                                <i class="fas fa-truck-fast mh-30 stock-icon '. $stock_icon .'"></i>
                            </div>
                            <div class="col-4 text-end">
                                <p>$' . number_format($distributor->getUnitPrice(), 2) . '</p>
                            </div>
                        </div>
                    </a>

                    <!-- Modal Add To Basket -->
                    <div class="modal fade" action="" id="modal_add_to_basket_' . $product_id . '_' . $distributor_id . '" tabindex="-1" aria-labelledby="basket_label" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header basket-modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form name="form_add_to_basket" id="form_add_to_basket_' . $product_id . '_' . $distributor_id . '" method="post">
                                    <input type="hidden" name="product_id" value="'. $product->getId() .'">
                                    <input type="hidden" name="distributor_id" value="'. $product->getDistributorProducts()[0]->getDistributor()->getId() .'">
                                    <input type="hidden" name="price" value="'. $product->getDistributorProducts()[0]->getUnitPrice() .'">
                                    <input type="hidden" name="status" value="active">
                                    <input type="hidden" name="basket_name" value="Fluid Commerce">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-12 col-sm-5 text-center" id="basket_thumbnail text-center">
                                                <img src="/images/products/' . $product->getImage() . '" class="text-center" style="max-height: 250px !important;">
                                            </div>
                                            <div class="col-12 col-sm-7 text-center text-sm-start mt-3 mt-sm-0">
                                                <h4 id="basket_item_name">
                                                    ' . $product->getName() . ': ' . $product->getDosage() . $product->getUnit() . ', ' . $product->getSize() . ' Count
                                                </h4>
                                                <h5 id="basket_item_price" class="text-primary modal_price text-center text-sm-start">
                                                    $' . $product->getDistributorProducts()[0]->getUnitPrice() . '
                                                </h5>
                                                <div class="modal_availability">
                                                    '. $stock_copy .'
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <input 
                                                            type="text" 
                                                            list="qty_list_' . $product_id . '_' . $distributor_id . '" 
                                                            name="qty" 
                                                            id="qty_' . $product_id . '_' . $distributor_id . '" 
                                                            class="form-control modal-basket-qty" 
                                                            value="1"
                                                            '. $disabled .'
                                                        />
                                                        <datalist
                                                            id="qty_list_' . $product_id . '_' . $distributor_id . '"
                                                        >
                                                            <option>1</option>
                                                            <option>2</option>
                                                            <option>3</option>
                                                            <option>4</option>
                                                            <option>5</option>
                                                            <option>6</option>
                                                            <option>7</option>
                                                            <option>8</option>
                                                            <option>9</option>
                                                            <option>10</option>
                                                            <option>11</option>
                                                            <option>12</option>
                                                            <option>13</option>
                                                            <option>14</option>
                                                            <option>15</option>
                                                            <option>16</option>
                                                            <option>17</option>
                                                            <option>18</option>
                                                            <option>19</option>
                                                            <option>20</option>
                                                            <option id="qty_custom">Enter Quantity</option>
                                                        </datalist>
                                                        <div class="hidden_msg" id="error_qty_' . $product_id . '_' . $distributor_id . '">
                                                            Required Field
                                                        </div>
                                                        <div class="hidden_msg" id="error_stock_' . $product_id . '_' . $distributor_id . '">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <button 
                                                            type="submit" 
                                                            class="btn btn-primary w-100 '. $btn_disabled .'"
                                                            '. $disabled .'
                                                        >
                                                            ADD TO BASKET
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer d-block">
                                        <div class="row">
                                            <div class="col-12 col-sm-6 text-start" style="padding-bottom: 0.75rem">
                                                <a href="" class="me-4 d-inline-block btn_item_facts">
                                                    Item Facts
                                                </a>
                                                <a href="" class="me-4 d-inline-block btn_shipping">
                                                    Shipping
                                                </a>
                                                <a href="" class="d-inline-block btn_taxes">
                                                    Taxes
                                                </a>
                                            </div>
                                            <div class="col-12 col-sm-6 text-end" style="padding-bottom: 0.75rem">
                                                <i class="fa-regular fa-user me-3"></i> <b>' . $distributor_id . ' - ' . $distributor->getDistributor()->getDistributorName() . '</b>
                                            </div>

                                            <!-- Panel Item Facts -->
                                            <div class="col-12 modal_availability" id="panel_item_facts_' . $product_id . '_' . $distributor_id . '">
                                                <div class="row mt-sm-4">
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                Unit Price
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                $' . number_format($distributor->getUnitPrice() / $product->getSize(), 2) . '
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-none d-sm-block col-sm-2"></div>
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                Manufacturer
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                ' . $distributor->getDistributor()->getDistributorName() . '
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-sm-4">
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                Fluid ID
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                $' . $distributor->getDistributor()->getId() . '
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-none d-sm-block col-sm-2"></div>
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                Man No
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                ' . $distributor->getDistributorNo() . '
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-sm-4">
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                SKU
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                ' . $distributor->getSku() . '
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-none d-sm-block col-sm-2"></div>
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                Seller Profile
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <a href="">' . $distributor->getDistributor()->getDistributorName() . '</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-sm-4">
                                                    <div class="col-12 col-sm-5">
                                                        <div class="row">
                                                            <div class="col-6 fw-bold">
                                                                List Price
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                $' . number_format($distributor->getUnitPrice(), 2) . '
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Panel shipping -->
                                            <div class="col-12" id="panel_shipping_' . $product_id . '_' . $distributor_id . '">
                                                <div class="row">
                                                    <div class="col-12 modal_availability">
                                                        Standard shipping transit times vary depending on location.
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-4 fw-bold">
                                                        Shipping Speed
                                                    </div>
                                                    <div class="col-4 fw-bold">
                                                        Cost
                                                    </div>
                                                    <div class="col-4 fw-bold">
                                                        Free Over
                                                    </div>
                                                </div>
                                                <div class="row mb-4">
                                                    <div class="col-4">
                                                        Default
                                                    </div>
                                                    <div class="col-4">
                                                        $6.99
                                                    </div>
                                                    <div class="col-4">
                                                        $100
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Panel Taxes -->
                                            <div class="col-12 modal_availability border-bottom-0" id="panel_taxes_' . $product_id . '_' . $distributor_id . '">
                                                Med-Vet Collects Sales Tax in states where we have physical
                                                presence (or nexus), including but not limited to Alabama,
                                                Colorado, Connecticut, Hawaii, Illinois, Indiana, Kentucky,
                                                Maine, Maryland, Massachusetts, Michigan, Minnesota, Mississippi,
                                                New Jersey, North Carolina, North Dakota, Ohio, Oklahoma,
                                                Pennsylvania, Rhode Island, South Dakota, Vermont, & Wisconsin.
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                }
                $response .= '
                    </div>
    
                    <!-- Panels -->
                    <div class="col-12 ps-3 pe-3">
                        <div class="col-12 search-panels-container" id="search_panels_container_'. $product->getId() .'" style="display:none;">
    
                            <!-- Description -->
                            <div class="hidden" id="details_'. $product->getId() .'">
                                <h3 class="pb-3 pt-3">Item Description</h3>
                                '. $product->getDescription() .'
                            </div>
    
                            <!-- Shopping lists -->
                            <div class="collapse panel_lists" id="lists_'. $product->getId() .'">
                                <h3 class="pb-3 pt-3">Shopping Lists</h3>
                                <p id="lists_no_data_'. $product->getId() .'">
                                    You do not currently have any shopping lists on Fluid
                                    <br><br>
                                    Have shopping lists with your suppliers? We\'ll import them!
                                    Send us a message using the chat icon in the lower right corner and we will
                                    help import you lists!
    
                                    You can also create new lists using the Create List button below
                                </p>
                            </div>
    
                            <!-- Track -->
                            <div class="collapse" id="track_'. $product->getId() .'">
                            </div>
    
                            <!-- Notes -->
                            <div class="collapse" id="notes_'. $product->getId() .'">
                                <h3 class="pb-3 pt-3">Item Notes</h3>
                            </div>
    
                            <!-- Reviews -->
                            <div class="collapse review_panel" id="reviews_'. $product->getId() .'">
                                <h3 class="pb-3 pt-3">Reviews</h3>
                                <h5>No reviews yet!</h5>
                                <p id="reviews_no_data">Reviews help thousands of veterinary purchasers know about your experience
                                    with this. Submit your review below.
                                    <br><br>
                                    <a href="" class="btn btn-primary btn_create_review" data-bs-toggle="modal" data-product-id="'. $product->getId() .'" data-bs-target="#modal_review">
                                        WRITE A REVIEW
                                    </a>
                                </p>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>';
            }

            $current_page = $request->request->get('page_no');
            $last_page = $this->page_manager->lastPage($results);

            $response .= '
                <!-- Pagination -->
                <div class="row">
                    <div class="col-12">';

            if($last_page > 1) {

                $previous_page_no = $current_page - 1;
                $url = '/clinics/inventory/';
                $previous_page = $url . $previous_page_no;

                $response .= '
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

                $response .= '
                <li class="page-item '. $disabled .'">
                    <a class="page-link" '. $list_id .' aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page - 1 .'" href="'. $previous_page .'">
                        <span aria-hidden="true">&laquo;</span> Previous
                    </a>
                </li>';

                for($i = 1; $i <= $last_page; $i++) {

                    $active = '';

                    if($i == (int) $current_page){

                        $active = 'active';
                    }

                    $response .= '
                    <li class="page-item '. $active .'">
                        <a class="page-link" '. $list_id .' data-page-id="'. $i .'" href="'. $url . $i .'">'. $i .'</a>
                    </li>';
                }

                $disabled = 'disabled';
                $data_disabled = 'true';

                if($current_page < $last_page) {

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $response .= '
                <li class="page-item '. $disabled .'">
                    <a class="page-link" '. $list_id .' aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page + 1 .'" href="'. $url . $current_page + 1 .'">
                        Next <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>';

                $response .= '
                        </ul>
                    </nav>
                </div>';
            }

            $response .= '
            <!-- Modal Review -->
            <div class="modal fade" id="modal_review" tabindex="-1" aria-labelledby="review_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <div class="modal-header basket-modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form name="form_review" id="form_review" method="post">
                            <input type="hidden" name="review_product_id" id="review_product_id" value="0">
                            <input type="hidden" name="rating" id="rating" value="0">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-12 col-sm-4 mb-0">
                                        <h6>Review guidelines</h6>
                                        <br>
                                        <ul>
                                            <li>
                                                <b>Be Helpful and Relevant</b> - Reviews are intended to provide helpful,
                                                meaningful content to customers.
                                                <br>
                                                <br>
                                            </li>
                                            <li>
                                                <b>Be Honest</b> - In order to preserve the integrity of our reviews, content
                                                should be an accurate representation of your experience with this item.
                                                Fluid strictly forbids commercial solicitations or compensation in exchange
                                                for positive reviews.
                                                <br>
                                                <br>
                                            </li>
                                            <li>
                                                <b>Stay Relevant</b> - Reviews should focus on the pros and cons of the item.
                                                Reviews focusing on the supplier or manufacturer directly will not be
                                                approved.
                                                <br>
                                                <br>
                                            </li>
                                            <li>
                                                <b>Acknowledge</b> - Please note that by submitting you acknowledge that
                                                your review may be used by the manufacturer in future marketing materials
                                                for this brand.
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-12 col-sm-8 mb-0 ps-3 ps-sm-5">
                                        <h5>Write a review for:</h5>
                                        <h6 class="text-primary">Terbinafine Tablets: 250mg, 100 Count</h6>
                                        <br>
                                        RATE THIS ITEM
                                        <div id="review_rating" class="mb-3 mt-2">
                                            <div style="position: relative; display: inline-block">
                                                <i class="star star-under fa fa-star star-lg" id="star-under-1"></i>
                                                <i class="star star-over fa fa-star star-lg" id="star-over-1"></i>
                                            </div>
                                            <div style="position: relative; display: inline-block">
                                                <i class="star star-under fa fa-star star-lg" id="star-under-2"></i>
                                                <i class="star star-over fa fa-star star-lg" id="star-over-2"></i>
                                            </div>
                                            <div style="position: relative; display: inline-block">
                                                <i class="star star-under fa fa-star star-lg" id="star-under-3"></i>
                                                <i class="star star-over fa fa-star star-lg" id="star-over-3"></i>
                                            </div>
                                            <div style="position: relative; display: inline-block">
                                                <i class="star star-under fa fa-star star-lg" id="star-under-4"></i>
                                                <i class="star star-over fa fa-star star-lg" id="star-over-4"></i>
                                            </div>
                                            <div style="position: relative; display: inline-block">
                                                <i class="star star-under fa fa-star star-lg" id="star-under-5"></i>
                                                <i class="star star-over fa fa-star star-lg" id="star-over-5"></i>
                                            </div>
                                            <div class="hidden_msg" id="error_rating">
                                                Please click to rate this item
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label>Title</label>
                                                <input name="review_title" id="review_title" type="text" class="form-control">
                                            </div>
                                            <div class="hidden_msg" id="error_review_title">
                                                Required Field
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label>Review</label>
                                                <textarea rows="4" name="review" id="review" class="form-control"></textarea>
                                                <div class="hidden_msg" id="error_review">
                                                    Required Field
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 col-sm-6">
                                                <label>
                                                    Username
                                                </label>
                                                <input 
                                                    type="text" 
                                                    name="review_username" 
                                                    id="review_username" 
                                                    class="form-control"
                                                    value="'. $this->getUser()->getReviewUserName() .'"
                                                >
                                                <div class="hidden_msg" id="error_review_username">
                                                    Required Field
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label>
                                                    Position
                                                </label>
                                                <select name="position" id="review_position" class="form-control">
                                                    <option value=""></option>
                                                    <option value="Technician">Technician</option>
                                                    <option value="Credentialed Technician">Credentialed Technician</option>
                                                    <option value="Veterinary Assistant">Veterinary Assistant</option>
                                                    <option value="Doctor">Doctor</option>
                                                    <option value="Doctor, Owner">Doctor, Owner</option>
                                                    <option value="Doctor, Diplomate">Doctor, Diplomate</option>
                                                    <option value="Doctor, Diplomate, Owner">Doctor, Diplomate, Owner</option>
                                                    <option value="Non-DVM Owner">Non-DVM Owner</option>
                                                    <option value="Inventory Manager">Inventory Manager</option>
                                                    <option value="Hospital Manager">Hospital Manager</option>
                                                    <option value="Office Staff">Office Staff</option>
                                                </select>
                                                <div class="hidden_msg" id="error_review_position">
                                                    Required Field
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger btn-sm w-100 w-sm-100" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="btn btn-primary btn-sm w-100 w-sm-100">CREATE REVIEW</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';

        } else {

            $response = 'Please use the search bar above';
        }

        return new JsonResponse($response);
    }

    #[Route('clinics/product-favourite', name: 'product_favourite')]
    public function productfavourite(Request $request): Response
    {
        $data = $request->request;
        $clinic = $this->getUser()->getClinic();
        $product_id = $data->get('product_id');
        $product = $this->em->getRepository(Products::class)->find($product_id);

        $product_favourite = $this->em->getRepository(ProductFavourites::class)->findOneBy([
            'product' => $product_id,
            'clinic' => $clinic->getId()
        ]);

        if($product_favourite == null){

            $product_favourite = new ProductFavourites();

            $product_favourite->setClinic($clinic);
            $product_favourite->setProduct($product);

            $this->em->persist($product_favourite);

            $response = true;

        } else {

            $this->em->remove($product_favourite);

            $response = false;
        }

        $this->em->flush();

        return new JsonResponse($response);
    }
}