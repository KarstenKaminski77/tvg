<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
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
    const ITEMS_PER_PAGE = 1;
    private $page_manager;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, PaginationManager $pagination)
    {
        $this->page_manager = $pagination;
        $this->em = $entityManager;
    }

    #[Route('/clinics/inventory/{page_no}', name: 'frontend', requirements: ['page' => '\i+'])]
    public function index(Request $request): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $response = '';

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

            foreach($results->getQuery()->getArrayResult() as $product){

                $product_notes = $this->em->getRepository(ProductNotes::class)->findNotes($product['id'], $user->getClinic()->getId());
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
                                <div class="alert-warning p-2 '. $class .'" id="product_notes_label_'. $product['id'] .'">'. $note .'</div>
                                <!-- Product main container -->
                                <div class="col-12 col-sm-9 ps-3 text-center text-sm-start">
                                    <div class="row">
                                        <!-- Thumbnail -->
                                        <div class="col-12 col-sm-2 pt-3">
                                            <img src="/images/products/'. $product['image'] .'" class="img-fluid prd-img">
                                        </div>
                                        <!-- Description -->
                                        <div class="col-12 col-sm-10 pt-3 pb-3">
                                           <h4>'. $product['name'] .': '. $product['dosage'] . $product['unit'] .', '. $product['size'] .' Count</h4>
                                           <p>From $'. number_format($product['unitPrice'] / $product['size'], 2) .' / '. strtolower($product['form']) .'</p>
                                            <!-- Product rating -->
                                            <div id="parent_'. $product['id'] .'" class="mb-3 mt-2 d-inline-block">
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
                                                'product_id' => $product['id']
                                            ])->getContent() .'
                                        </div>
        
                                        <!-- Collapsable panel buttons -->
                                        <div class="col-12 search-panels-header">
                                            <!-- Description -->
                                            <button class="btn btn-sm btn-light info ps-0 pe-4 pe-sm-0 btn_details" type="button" data-product-id="'. $product['id'] .'">
                                                <i class="fa-regular fa-circle-question"></i> <span class="d-none d-sm-inline">Details</span>
                                            </button>
                                            <!-- Shopping lists -->
                                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_lists" type="button" data-product-id="'. $product['id'] .'">
                                                <i class="fa-solid fa-list"></i> <span class="d-none d-sm-inline">Lists</span>
                                            </button>
                                            <!-- Tracking -->
                                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_track" type="button" data-product-id="'. $product['id'] .'">
                                                <i class="fa-regular fa-eye"></i> <span class="d-none d-sm-inline">Track</span>
                                            </button>
                                            <!-- Notes -->
                                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_notes" type="button" data-product-id="'. $product['id'] .'">
                                                <i class="fa-solid fa-pencil"></i> <span class="d-none d-sm-inline">Notes</span>
                                            </button>
                                            <!-- Reviews -->
                                            <button class="btn btn-sm btn-light info pe-4 pe-sm-0 btn_reviews" type="button" data-product-id="'. $product['id'] .'">
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

                foreach($product['distributorProducts'] as $distributor) {
                    $product_id = $product['id'];
                    $distributor_id = $distributor['distributor']['id'];
                    $response .= '<a href=""
                                           class="basket_link"
                                           data-product-id="' . $product['id'] . '"
                                           data-distributor-id="' . $distributor_id . '"
                                           data-bs-toggle="modal"
                                           data-bs-target="#modal_add_to_basket_' . $product_id . '_' . $distributor_id . '"
                                        >
                                            <div class="row distributor-store-row">
                                                <div class="col-4">
                                                    <img src="/images/logos/' . $distributor['distributor']['logo'] . '" class="img-fluid mh-30">
                                                </div>
                                                <div class="col-4 text-center">
                                                    <i class="fas fa-truck-fast mh-30 stock-icon in-stock"></i>
                                                </div>
                                                <div class="col-4 text-end">
                                                    <p>$' . number_format($distributor['unitPrice'], 2) . '</p>
                                                </div>
                                            </div>
                                        </a>
        
                                        <!-- Modal Add To Basket -->
                                        <div class="modal fade" id="modal_add_to_basket_' . $product_id . '_' . $distributor_id . '" tabindex="-1" aria-labelledby="basket_label" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header basket-modal-header">
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form name="form_add_to_basket" id="form_add_to_basket_' . $product_id . '_' . $distributor_id . '" method="post">
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-12 col-sm-5 text-center" id="basket_thumbnail">
                                                                    <img src="/images/products/' . $product['image'] . '" class="text-center prd-basket-img-sm">
                                                                </div>
                                                                <div class="col-12 col-sm-7 text-center text-sm-start mt-3 mt-sm-0">
                                                                    <h4 id="basket_item_name">
                                                                        ' . $product['name'] . ': ' . $product['dosage'] . $product['unit'] . ', ' . $product['size'] . ' Count
                                                                    </h4>
                                                                    <h5 id="basket_item_price" class="text-primary modal_price text-center text-sm-start">
                                                                        $' . $product['distributorProducts'][0]['unitPrice'] . '
                                                                    </h5>
                                                                    <div class="modal_availability">
                                                                        <span class="is_available">In Stock</span> This item is in stock and ready to ship
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <input type="text" list="qty_list_' . $product_id . '_' . $distributor_id . '" id="qty_' . $product_id . '_' . $distributor_id . '" class="form-control" />
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
                                                                    <i class="fa-regular fa-user me-3"></i> <b>' . $distributor_id . ' - ' . $distributor['distributor']['distributorName'] . '</b>
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
                                                                                    $' . number_format($distributor['unitPrice'] / $product['size'], 2) . '
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
                                                                                    ' . $distributor['distributor']['distributorName'] . '
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
        
                                                                    <div class="row mt-sm-4">
                                                                        <div class="col-12 col-sm-5">
                                                                            <div class="row">
                                                                                <div class="col-6 fw-bold">
                                                                                    TVG ID
                                                                                </div>
                                                                                <div class="col-6 text-end">
                                                                                    $' . $distributor['distributor']['id'] . '
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
                                                                                    ' . $distributor['distributorNo'] . '
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
                                                                                    ' . $distributor['sku'] . '
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
                                                                                    <a href="">' . $distributor['distributor']['distributorName'] . '</a>
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
                                                                                    $' . number_format($distributor['unitPrice'], 2) . '
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
                                    <div class="col-12 search-panels-container" id="search_panels_container_'. $product['id'] .'" style="display:none;">
        
                                        <!-- Description -->
                                        <div class="hidden" id="details_'. $product['id'] .'">
                                            <h3 class="pb-3 pt-3">Item Description</h3>
                                            '. $product['description'] .'
                                        </div>
        
                                        <!-- Shopping lists -->
                                        <div class="collapse panel_lists" id="lists_'. $product['id'] .'">
                                            <h3 class="pb-3 pt-3">Shopping Lists</h3>
                                            <p id="lists_no_data_'. $product['id'] .'">
                                                You do not currently have any shopping lists on TVG
                                                <br><br>
                                                Have shopping lists with your suppliers? We\'ll import them!
                                                Send us a message using the chat icon in the lower right corner and we will
                                                help import you lists!
        
                                                You can also create new lists using the Create List button below
                                            </p>
                                        </div>
        
                                        <!-- Track -->
                                        <div class="collapse" id="track_'. $product['id'] .'">
                                            <h3 class="pb-3 pt-3">Availability Tracker</h3>
                                            <p id="track_no_data">
                                                Create custom alerts when a backordered item comes back in stock. Set a notification
                                                for how you would like to be notified and which suppliers you would like to track.
                                                Once an item comes back in stock and you are notified, the tracker will automatically
                                                turn off. You can also view a list of all tracked items in your shopping list.
                                                Note: TVG cannot track the availability of items that are drop shipped directly
                                                from the vendor.
                                            </p>
                                        </div>
        
                                        <!-- Notes -->
                                        <div class="collapse" id="notes_'. $product['id'] .'">
                                            <h3 class="pb-3 pt-3">Item Notes</h3>
                                        </div>
        
                                        <!-- Reviews -->
                                        <div class="collapse" id="reviews_'. $product['id'] .'">
                                            <h3 class="pb-3 pt-3">Reviews</h3>
                                            <h5>No reviews yet!</h5>
                                            <p id="reviews_no_data">Reviews help thousands of veterinary purchasers know about your experience
                                                with this. Submit your review below.
                                                <br><br>
                                                <a href="" class="btn btn-primary btn_create_review" data-bs-toggle="modal" data-product-id="'. $product['id'] .'" data-bs-target="#modal_review">
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
                        <ul class="pagination">';
                            if($current_page > 1){
                                $response .= '<li class="page-item"><a class="page-link" data-page-id="'. $current_page - 1 .'" href="'. $previous_page .'">Previous</a></li>';
                            }

                            for($i = 1; $i <= $last_page; $i++) {

                                $active = '';

                                if($i == (int) $current_page){

                                    $active = 'active';
                                }

                                $response .= '
                                <li class="page-item '. $active .'"><a class="page-link" data-page-id="'. $i .'" href="'. $url . $i .'">'. $i .'</a></li>';
                            }

                            if($current_page < $last_page) {
                                $response .= '
                                <li class="page-item"><a class="page-link" data-page-id="'. $current_page + 1 .'" href="'. $url . $current_page + 1 .'">Next</a></li>';
                            }
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
                                                    TVG strictly forbids commercial solicitations or compensation in exchange
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
        }

        return new JsonResponse($response);
    }
}