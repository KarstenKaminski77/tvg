<?php

namespace App\Controller;

use App\Entity\BasketItems;
use App\Entity\Baskets;
use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\Distributors;
use App\Entity\ProductNotes;
use App\Entity\Products;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    const ITEMS_PER_PAGE = 1;
    private $page_manager;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, PaginationManager $pagination)
    {
        $this->page_manager = $pagination;
        $this->em = $entityManager;
    }

    #[Route('/clinics/inventory/inventory-add-to-basket', name: 'inventory_add_to_basket')]
    public function addToBasketAction(Request $request): Response
    {
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $distributor = $this->em->getRepository(Distributors::class)->find($request->get('distributor_id'));
        $product = $this->em->getRepository(Products::class)->find($request->get('product_id'));
        $basket = $this->em->getRepository(Baskets::class)->findOneBy([
            'clinic' => $this->getUser()->getClinic()->getId(),
            'status' => 'active'
        ]);

        if($basket == null){

            $basket = new Baskets();
        }

        $basket->setClinic($clinic);
        $basket->setDistributor($distributor);
        $basket->setName($request->get('basket_name'));
        $basket->setStatus($request->get('status'));
        $basket->setSavedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($basket);
        $this->em->flush();

        $basket_item = $this->em->getRepository(BasketItems::class)->findOneBy([
            'basket' => $basket,
            'product' => $product,
            'distributor' => $distributor
        ]);

        if($basket_item == null){

            $basket_item = new BasketItems();
        }

        $basket_item->setBasket($basket);
        $basket_item->setDistributor($distributor);
        $basket_item->setProduct($product);
        $basket_item->setName($product->getName());
        $basket_item->setQty($request->get('qty'));
        $basket_item->setUnitPrice($request->get('price'));
        $basket_item->setTotal($request->get('qty') * $request->get('price'));

        $this->em->persist($basket_item);
        $this->em->flush();

        // Get total items in basket
        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket->getId());

        $basket->setTotal($totals[0]['total']);

        $this->em->persist($basket);
        $this->em->flush();

        $response = [
            'message' => '<b><i class="fas fa-check-circle"></i> '. $product->getName() .' added to your basket.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_count' => $totals[0]['item_count'],
            'product_id' => $product->getId(),
            'distributor_id' => $distributor->getId(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/inventory-update-basket', name: 'inventory_update_basket')]
    public function updateBasketAction(Request $request): Response
    {
        $basket_item = $this->em->getRepository(BasketItems::class)->find($request->request->get('item_id'));
        $basket_id = $basket_item->getBasket()->getId();
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);

        if($basket_item != null){

            $basket_item->setQty($request->request->get('qty'));
            $basket_item->setTotal($basket_item->getUnitPrice() * $request->request->get('qty'));

            $this->em->persist($basket_item);
            $this->em->flush();
        }

        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket_id);

        if($basket != null){

            $basket->setTotal(number_format($totals[0]['total'],2));

            $this->em->persist($basket);
            $this->em->flush();

        }

        $response = [

            'message' => '<b><i class="fas fa-check-circle"></i> '. $basket_item->getProduct()->getName() .' updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_id,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/inventory-remove-basket-item', name: 'inventory_remove_basket_item')]
    public function removeBasketItemAction(Request $request): Response
    {
        $basket_item = $this->em->getRepository(BasketItems::class)->find($request->request->get('item_id'));
        $basket_id = $basket_item->getBasket()->getId();
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);

        if($basket_item != null){

            $this->em->remove($basket_item);
            $this->em->flush();
        }

        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket_id);

        if($basket != null){

            $basket->setTotal(number_format($totals[0]['total'],2));

            $this->em->persist($basket);
            $this->em->flush();

        }

        $response = [

            'message' => '<b><i class="fas fa-check-circle"></i> '. $basket_item->getProduct()->getName() .' removed.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_id,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/inventory-clear-basket', name: 'inventory_clear_basket')]
    public function clearBasketAction(Request $request): Response
    {
        $basket_id = $request->request->get('basket_id');
        $basket_items = $this->em->getRepository(BasketItems::class)->findAll(['basket' => $basket_id]);
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);

        if($basket_items != null){

            foreach($basket_items as $item) {

                $this->em->remove($item);
                $this->em->flush();
            }
        }

        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket_id);

        if($basket != null){

            $basket->setTotal($total = number_format($totals[0]['total'] ?? 0,2));

            $this->em->persist($basket);
            $this->em->flush();

        }

        $response = [

            'message' => '<b><i class="fas fa-check-circle"></i> All items removed from basket.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_id,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/get-basket', name: 'get_basket')]
    public function getBasketAction(Request $request): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $basket_id = $request->request->get('basket_id');
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $clinic_totals = $this->em->getRepository(Baskets::class)->getClinicTotalItems($this->getUser()->getClinic()->getId());

        $total_clinic = number_format($clinic_totals[0]['total'] ?? 0,2);
        $count_clinic = $clinic_totals[0]['item_count'] ?? 0;
        $bg_primary = '';

        if($count_clinic > 0){

            $bg_primary = 'bg-primary';
        }

        $response = '<div class="row">
            <!-- Left Column -->
            <div class="col-12 col-md-2 col-100">
                <div class="row border-bottom text-center pt-2 pb-2">
                    <b>All Baskets</b>
                </div>
                <div class="row" style="background: #f4f8fe">
                    <div class="col-6 border-bottom pt-1 pb-1 text-center">
                        <span class="d-block text-primary">'. $count_clinic .'</span>
                        <span class="d-block text-truncate">Items</span>
                    </div>
                    <div class="col-6 border-bottom pt-1 pb-1 text-center">
                        <span class="d-block text-primary">$'. $total_clinic .'</span>
                        <span class="d-block text-truncate">Subtotal</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 border-bottom">
                        <a href="#" data-basket-id="5" class=" pt-3 pb-3 d-block basket-link">
                            <span class="d-inline-block align-baseline">All Baskets</span>
                            <span class="float-end basket-item-count-empty '. $bg_primary .'">
                                '. $count_clinic .'
                            </span>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 border-bottom">
                        <a href="#" data-basket-id="5" class=" pt-3 pb-3 d-block basket-link">
                            <span class="d-inline-block align-baseline">Fluid</span>
                            <span class="float-end basket-item-count-empty '. $bg_primary .'">
                                '. $count_clinic .'
                            </span>
                        </a>
                    </div>
                </div>
                <div class="row border-bottom">
                    <div class="col-4 col-sm-12 col-md-4 pt-3 pb-3 saved-baskets">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <div class="col-8 col-sm-12 col-md-8 pt-3 pb-3">
                        <h6 class="text-primary">Saved Baskets</h6>
                        <span class="info">View baskets</span>
                    </div>
                </div>
            </div>
            <!-- Right Column -->
            <div class="col-12 col-md-10 col-100 border-left">
                <!-- Basket Name -->
                <div class="row">
                    <div class="col-12 bg-primary text-center pt-3 pb-3">
                        <h4 class="text-white">All Baskets</h4>
                        <span class="text-white">
                            Manage All Your Shopping Carts In One Place
                        </span>
                    </div>
                </div>
                <!-- Basket Actions Upper Row -->
                <div class="row">
                    <div class="col-12 text-center border-bottom pt-3 pb-3">
                        <a href="#">
                            <i class="fa-solid fa-arrow-rotate-right me-5 me-sm-2"></i><span class=" d-none d-sm-inline-block pe-3">Refresh Basket</span>
                        </a>
                        <a href="#">
                            <i class="fa-solid fa-print me-5 me-sm-2"></i><span class=" d-none d-sm-inline-block pe-3">Print</span>
                        </a>
                        <a href="#">
                            <i class="fa-solid fa-basket-shopping me-0 me-sm-2"></i><span class=" d-none d-sm-inline-block pe-3">Saved Baskets</span>
                        </a>
                        <a href="#" id="return_to_search">
                            <i class="fa-solid fa-magnifying-glass me-0 me-sm-2"></i><span class=" d-none d-sm-inline-block pe-3">Back To Search</span>
                        </a>
                    </div>
                </div>';

        if(count($basket->getBasketItems()) > 0) {

            $response .= '
                <!-- Basket Actions Lower Row -->
                <div class="row">
                    <div class="col-12 text-center border-bottom pt-3 pb-3">
                        <a href="#">
                            <i class="fa-regular fa-bookmark me-5 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Save All For Later</span>
                        </a>
                        <a href="#" class="clear-basket" data-basket-id="' . $basket_id . '">
                            <i class="fa-solid fa-trash-can me-5 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Clear Basket</span>
                        </a>
                        <a href="#" class="refresh-basket" data-basket-id="'. $basket_id .'">
                            <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Refresh Basket</span>
                        </a>
                        <a href="#">
                            <i class="fa-solid fa-clock-rotate-left me-5 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Restore Basket</span>
                        </a>
                        <a href="#">
                            <i class="fa-solid fa-basket-shopping me-0 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Save Basket</span>
                        </a>
                    </div>
                </div>';
        }

        $response .= '
                <!-- Basket Items -->
                <div class="row col-container d-flex border-0 m-0">
                    <div class="col-12 col-md-9 col-cell border-right">';

            $i = -1;

            if(count($basket->getBasketItems()) > 0) {

                foreach ($basket->getBasketItems() as $item) {

                    $i++;
                    $product = $basket->getBasketItems()[$i]->getProduct();

                    if($product->getStockCount() > 0){

                        $stock_badge = '<span class="badge bg-success me-2">In Stock</span>';

                    } else {

                        $stock_badge = '<span class="badge bg-danger me-2">Out Of Stock</span>';
                    }

                    $response .= '
                    <div class="row">
                        <!-- Thumbnail -->
                        <div class="col-12 col-sm-2 text-center pt-3 pb-3">
                            <img class="img-fluid basket-img" src="/images/products/' . $product->getImage() . '">
                        </div>
                        <div class="col-12 col-sm-10 pt-3 pb-3">
                            <!-- Product Name and Qty -->
                            <div class="row">
                                <!-- Product Name -->
                                <div class="col-12 col-sm-7 pt-3 pb-3">
                                    <h6 class="fw-bold text-center text-sm-start text-primary lh-base">
                                        ' . $product->getName() . ': ' . $product->getDosage() . ' ' . $product->getUnit() . ', Each
                                    </h6>
                                </div>
                                <!-- Product Quantity -->
                                <div class="col-12 col-sm-5 pt-3 pb-3">
                                    <div class="row">
                                        <div class="col-4 text-center text-sm-end">$' . $item->getUnitPrice() . '</div>
                                        <div class="col-4">
                                            <input type="text" list="qty_list_' . $product->getId() . '" data-basket-item-id="' . $item->getId() . '" name="qty" class="form-control basket-qty" value="' . $item->getQty() . '">
                                            <datalist id="qty_list_' . $product->getId() . '">
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
                                        </div>
                                        <div class="col-4 text-center text-sm-start fw-bold">$' . $item->getTotal() . '</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Item Actions -->
                            <div class="row">
                                <div class="col-12">
                                    <!-- In Stock -->
                                    '. $stock_badge .'
                                    <!-- Shipping Policy -->
                                    <span class="badge bg-secondary" class="btn btn-secondary" data-bs-trigger="hover"
                                          data-bs-container="body" data-bs-toggle="popover" data-bs-placement="top"
                                          data-bs-content="Top popover">Shipping Policy</span>
                                    <!-- Remove Item -->
                                    <span class="badge bg-danger float-end">
                                        <a href="#" class="remove-item text-white" data-item-id="' . $item->getId() . '">Remove</a>
                                    </span>
                                    <!-- Save Item -->
                                    <span class="badge badge-light float-end me-2">
                                        <a href="#" class="link-secondary">Save Item For later</a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
            } else {

                $response .= '
                    <div class="row">
                        <div class="col-12 text-center pt-4">
                            <p>
                            <h5>Your basket at Fluid Commerce is currently empty </h5><br>
                            Were you expecting to see items here? View copies of the items most recently added<br> 
                            to your basket and restore a basket if needed.
                            </p>
                        </div>
                    </div>';
            }

            $response .= '
                    </div>
                    <!-- Basket Summary -->
                    <div class="col-12 col-md-3 pt-3 pb-3 pe-0 col-cell">
                        <div class="row">
                            <div class="col-12 text-truncate">
                                <span class="info">Subtotal:</span>
                                <h5 class="d-inline-block text-primary float-end">$'. $basket->getTotal() .'</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 info">
                                Shipping: <span class="float-end fw-bold">$6.99</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 pt-4 text-center">
                                <a href="" class="btn btn-primary w-100">
                                    PROCEED TO CHECKOUT <i class="fa-solid fa-circle-right ps-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/{page_no}', name: 'search_results', requirements: ['page' => '\i+'])]
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

        if($request->get('keyword') != null) {

            $products = $this->em->getRepository(Products::class)->findByKeystring($request->get('keyword'));
            $results = $this->page_manager->paginate($products, $request, self::ITEMS_PER_PAGE);

            foreach($results->getQuery()->getResult() as $product){

                $product_notes = $this->em->getRepository(ProductNotes::class)->findNotes($product->getId(), $user->getClinic()->getId());
                $baskets = $this->em->getRepository(Baskets::class)->findBy(['clinic' => $user->getClinic()->getId()]);

                $note = '';
                $class = '';

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

                $response .= '<div class="row mb-4 prd-container p-0 ms-1 ms-sm-0 me-1 me-sm-0">';

                $response .= '
                                <div class="alert-warning p-2 '. $class .'" id="product_notes_label_'. $product->getId() .'">'. $note .'</div>
                                <!-- Product main container -->
                                <div class="col-12 col-sm-9 ps-3 text-center text-sm-start">
                                    <div class="row">
                                        <!-- Thumbnail -->
                                        <div class="col-12 col-sm-2 pt-3 text-center">
                                            <img src="/images/products/'. $product->getImage() .'" class="img-fluid prd-img">
                                        </div>
                                        <!-- Description -->
                                        <div class="col-12 col-sm-10 pt-3 pb-3">
                                           <h4>'. $product->getName() .': '. $product->getDosage() . $product->getUnit() .', '. $product->getSize() .' Count</h4>
                                           <p>From $'. number_format($product->getUnitPrice() / $product->getSize(), 2) .' / '. strtolower($product->getForm()) .'</p>
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
                                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_notes" type="button" data-product-id="'. $product->getId() .'">
                                                <i class="fa-solid fa-pencil"></i> <span class="d-none d-sm-inline">Notes</span>
                                            </button>
                                            <!-- Reviews -->
                                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_reviews" type="button" data-product-id="'. $product->getId() .'">
                                                <i class="fa-regular fa-star"></i> <span class="d-none d-sm-inline">Reviews</span>
                                            </button>
                                            <div class="d-inline-block float-end text-end">
                                                <i class="fa-solid fa-sig"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        
                                <!-- Distributors -->
                                <div class="col-12 col-sm-3 mt-0 pt-3 pe-4 search-result-distributors">';

                foreach($product->getDistributorProducts() as $distributor) {
                    $product_id = $product->getId();
                    $distributor_id = $distributor->getDistributor()->getId();
                    $response .= '<a href=""
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
                                <i class="fas fa-truck-fast mh-30 stock-icon in-stock"></i>
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
                                                    <span class="is_available">In Stock</span> This item is in stock and ready to ship
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <input type="text" list="qty_list_' . $product_id . '_' . $distributor_id . '" name="qty" id="qty_' . $product_id . '_' . $distributor_id . '" class="form-control" />
                                                        <datalist id="qty_list_' . $product_id . '_' . $distributor_id . '">
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
                                                    </div>
                                                    <div class="col-6">
                                                        <button type="submit" class="btn btn-primary w-100">ADD TO BASKET</button>
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
                                <h3 class="pb-3 pt-3">Availability Tracker</h3>
                                <p id="track_no_data">
                                    Create custom alerts when a backordered item comes back in stock. Set a notification
                                    for how you would like to be notified and which suppliers you would like to track.
                                    Once an item comes back in stock and you are notified, the tracker will automatically
                                    turn off. You can also view a list of all tracked items in your shopping list.
                                    Note: Fluid cannot track the availability of items that are drop shipped directly
                                    from the vendor.
                                </p>
                            </div>
    
                            <!-- Notes -->
                            <div class="collapse" id="notes_'. $product->getId() .'">
                                <h3 class="pb-3 pt-3">Item Notes</h3>
                            </div>
    
                            <!-- Reviews -->
                            <div class="collapse" id="reviews_'. $product->getId() .'">
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

                if($current_page > 1){

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $response .= '<li class="page-item '. $disabled .'"><a class="page-link" aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page - 1 .'" href="'. $previous_page .'"><span aria-hidden="true">&laquo;</span> Previous</a></li>';

                for($i = 1; $i <= $last_page; $i++) {

                    $active = '';

                    if($i == (int) $current_page){

                        $active = 'active';
                    }

                    $response .= '
                    <li class="page-item '. $active .'"><a class="page-link" data-page-id="'. $i .'" href="'. $url . $i .'">'. $i .'</a></li>';
                }

                $disabled = 'disabled';
                $data_disabled = 'true';

                if($current_page < $last_page) {

                    $disabled = '';
                    $data_disabled = 'false';
                }

                $response .= '<li class="page-item '. $disabled .'"><a class="page-link" aria-disabled="'. $data_disabled .'" data-page-id="'. $current_page + 1 .'" href="'. $url . $current_page + 1 .'">Next <span aria-hidden="true">&raquo;</span></a></li>';

                $response .= '
                        </ul>
                    </nav>
                </div>';
            }

            $response .= '
                <!-- Modal Delete Note -->
                <div class="modal fade" id="modal_note_delete" tabindex="-1" aria-labelledby="note_delete_label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="note_delete_label">Delete User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-12 mb-0">
                                        Are you sure you would like to delete this note? This action cannot be undone.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="btn btn-danger btn-sm" id="delete_note" data-delete-note-id="" data-delete-product-id="">DELETE</button>
                            </div>
                        </div>
                    </div>
                </div>
        
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
                                                    <input type="text" name="review_username" id="review_username" class="form-control">
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
}