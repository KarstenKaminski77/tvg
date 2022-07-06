<?php

namespace App\Controller;

use App\Entity\BasketItems;
use App\Entity\Baskets;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\ListItems;
use App\Entity\Lists;
use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/clinics/inventory/get-lists', name: 'inventory_get_lists')]
    public function clinicsGetListsAction(Request $request): Response
    {
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $lists = $this->em->getRepository(Lists::class)->getClinicLists($clinic->getId());

        $product_id = (int) $request->request->get('id');

        $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3>';

        if(count($lists) == 0){

            $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3><p id="lists_no_data">You do not currently have any 
            shopping lists on Fluid<br><br>Have shopping lists with your suppliers? We\'ll import them! Send us a message 
            using the chat icon in the lower right corner and we will help import you lists! You can also create new lists 
            using the Create List button below</p>';

        } else {

            for($i = 0; $i < count($lists); $i++){

                if(count($lists[$i]->getListItems()) > 0) {

                    $item_count = true;

                    $item_id = $lists[$i]->getListItems()[0]->getList()->getListItems()[0]->getId();
                    $is_selected = false;

                    for($c = 0; $c < count($lists[$i]->getListItems()); $c++){

                        if($lists[$i]->getListItems()[$c]->getProduct()->getId() == $product_id){

                            $is_selected = true;
                            break;
                        }
                    }

                    if($is_selected) {

                        $icon = '<a href="" class="list_remove_item" data-id="' . $product_id . '" data-value="' . $item_id . '">
                            <i class="fa-solid fa-circle-check pe-2 list-icon list-icon-checked"></i>
                        </a>';

                    } else {

                        $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                    }

                } else {

                    $item_count = false;

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

                $response .= $this->getListRow($icon, $lists[$i]->getName(), $lists[$i]->getId(), $item_count, $request->request->get('keyword'));
            }
        }

        $response .= $this->listCreateNew($product_id, $request->request->get('keyword'));

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/remove-list-item', name: 'inventory_remove_list_item')]
    public function clinicsRemoveListsItemAction(Request $request): Response
    {
        $item_id = $request->request->get('id');
        $list_item = $this->em->getRepository(ListItems::class)->find($item_id);

        $this->em->remove($list_item);
        $this->em->flush();

        $response = $this->clinicsGetListsAction($request);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/remove-list-item', name: 'inventory_remove_list_item')]
    public function clinicsAddListsItemAction(Request $request): Response
    {
        $item_id = $request->request->get('item_id');
        $list_item = $this->em->getRepository(ListItems::class)->find($item_id);

        $this->em->remove($list_item);
        $this->em->flush();

        $response = $this->clinicsGetListsAction($request);

        return new JsonResponse($response);
    }

    #[Route('/clinics/manage-lists', name: 'manage_lists')]
    public function manageListsAction(Request $request): Response
    {
        $clinic = $this->getUser()->getClinic();
        $list_id = $request->request->get('list_id');

        if($list_id > 0){

            // Delete List Items
            $list_items = $this->em->getRepository(ListItems::class)->findBy([
                'list' => $list_id,
            ]);

            if(count($list_items) > 0){

                foreach($list_items as $item){

                    $this->em->remove($item);
                    $this->em->flush();
                }
            }

            // Delete List
            $list = $this->em->getRepository(Lists::class)->find($list_id);

            $this->em->remove($list);
            $this->em->flush();
        }

        $lists = $this->em->getRepository(Lists::class)->findBy([
            'clinic' => $clinic->getId(),
        ]);

        $html = '
        <div class="row">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3">
                <h4 class="text-white">Shopping Lists</h4>
                <span class="text-white">
                    Shopping lists make it easy to repurchase your most commonly ordered items
                    Add items to lists while you shop and save time on every order.
                </span>
            </div>
        </div>';

        foreach($lists as $list){

            $delete_icon = '';
            $list_type = 'Default';

            if($list->getIsProtected() == 0){

                $delete_icon = '
                <a 
                    href="#" 
                    class="delete-list float-end me-3 delete-list"
                    data-list-id="'. $list->getId() .'"
                    data-bs-toggle="modal" 
                    data-bs-target="#modal_list_delete"
                >
                    <i class="fa-solid fa-trash-can"></i>
                </a>';

                $list_type = 'Custom';
            }

            $html .= '
            <div class="row pt-3 pb-3 border-bottom-dashed">
                <div class="col-7 col-sm-9 col-md-10">
                    <b>'. $list->getName() .' ('. $list->getListItems()->count() .')</b><br>
                    Fluid '. $list_type .' List
                </div>
                <div class="col-5 col-sm-3 col-md-2">
                    <a 
                        href="#" 
                        class="view-list float-end text view-list"
                        data-list-id="'. $list->getId() .'"
                        data-keyword-string="'. $request->request->get('keyword') .'"
                        data-page-id="0"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    <a 
                        href=""
                        class="float-end me-3 edit-list"
                        data-list-id="'. $list->getId() .'"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    '. $delete_icon .'
                </div>
            </div>';
        }

        $html .= '
        <!-- Modal Delete List -->
        <div class="modal fade" id="modal_list_delete" tabindex="-1" aria-labelledby="list_delete_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="list_delete_label">Delete list</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-0">
                                Are you sure you would like to delete this list? This action cannot be undone.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">CANCEL</button>
                        <button type="button" class="btn btn-danger btn-sm" id="delete_list">DELETE</button>
                    </div>
                </div>
            </div>
        </div>';

        $flash = '<b><i class="fas fa-check-circle"></i> Shopping list successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [
            'response' => $html,
            'flash' => $flash,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/manage-list', name: 'inventory_manage_list')]
    public function clinicsManageListAction(Request $request): Response
    {
        $data = $request->request;
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $products = $this->em->getRepository(Products::class)->find($data->get('product_id'));
        $distributor = $this->em->getRepository(Distributors::class)->find($data->get('distributor_id'));
        $distributor_product = $this->em->getRepository(DistributorProducts::class)->findOneBy([
            'distributor' => $request->request->get('distributor_id'),
            'product' => $request->request->get('product_id')
        ]);

        $product_id = (int) $data->get('product_id');
        $list_id = (int) $data->get('list_id');
        $list_type = $data->get('list_type');
        $list_name = $data->get('list_name');

        // List
        if($list_id == 0){

            $list = new Lists();

            $list->setItemCount(1);
            $list->setListType($list_type);
            $list->setClinic($clinic);
            $list->setIsProtected(0);
            $list->setName($list_name);

            $this->em->persist($list);
            $this->em->flush();

        } else {

            $list = $this->em->getRepository(Lists::class)->find($list_id);
        }

        // List item
        $list_item = new ListItems();

        $list_item->setList($list);
        $list_item->setProduct($products);
        $list_item->setDistributor($distributor);
        $list_item->setDistributorProduct($distributor_product);
        $list_item->setName($products->getName());
        $list_item->setQty(1);

        $this->em->persist($list_item);
        $this->em->flush();

        $lists = $this->em->getRepository(Lists::class)->getClinicLists($clinic->getId($data->get('product_id')));

        $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3>';

        for($i = 0; $i < count($lists); $i++){

            if(count($lists[$i]->getListItems()) > 0) {

                $item_count = true;

                $item_id = $lists[$i]->getListItems()[0]->getList()->getListItems()[0]->getId();
                $is_selected = false;

                for($c = 0; $c < count($lists[$i]->getListItems()); $c++){

                    if($lists[$i]->getListItems()[$c]->getProduct()->getId() == $product_id){

                        $is_selected = true;
                        break;
                    }
                }

                if($is_selected) {

                    $icon = '<a href="" class="list_remove_item" data-id="' . $product_id . '" data-value="' . $item_id . '">
                            <i class="fa-solid fa-circle-check pe-2 list-icon list-icon-checked"></i>
                        </a>';

                } else {

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

            } else {

                $item_count = false;

                $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
            }

            $response .= $this->getListRow($icon, $lists[$i]->getName(), $lists[$i]->getId(), $item_count);
        }

        $response .= $this->listCreateNew($product_id);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/delete-list-item', name: 'inventory_delete_list_item')]
    public function clinicsDeleteListItemAction(Request $request): Response
    {
        $data = $request->request;
        $product_id = (int) $data->get('product_id');
        $list_id = (int) $data->get('list_id');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $list_item = $this->em->getRepository(ListItems::class)->find($list_id);

        $this->em->remove($list_item);
        $this->em->flush();

        $lists = $this->em->getRepository(Lists::class)->getClinicLists($clinic->getId());

        $response = '<h3 class="pb-3 pt-3">Shopping Lists</h3>';

        for($i = 0; $i < count($lists); $i++){

            if(count($lists[$i]->getListItems()) > 0) {

                $item_count = true;

                $item_id = $lists[$i]->getListItems()[0]->getList()->getListItems()[0]->getId();
                $is_selected = false;

                for($c = 0; $c < count($lists[$i]->getListItems()); $c++){

                    if($lists[$i]->getListItems()[$c]->getProduct()->getId() == $product_id){

                        $is_selected = true;
                        break;
                    }
                }

                if($is_selected) {

                    $icon = '<a href="" class="list_remove_item" data-id="' . $product_id . '" data-value="' . $item_id . '">
                            <i class="fa-solid fa-circle-check pe-2 list-icon list-icon-checked"></i>
                        </a>';

                } else {

                    $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
                }

            } else {

                $item_count = false;

                $icon = '<a href="" class="list_add_item" data-id="'. $product_id .'" data-value="'. $lists[$i]->getId() .'">
                            <i class="fa-solid fa-circle-plus pe-2 list-icon list-icon-unchecked"></i>
                        </a>';
            }

            $response .= $this->getListRow($icon, $lists[$i]->getName(), $lists[$i]->getId(), $item_count);
        }

        $response .= $this->listCreateNew($product_id);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/edit/list', name: 'inventory_edit_list')]
    public function clinicsEditListAction(Request $request): Response
    {
        $data = $request->request;
        $list = $this->em->getRepository(Lists::class)->getIndividualList($data->get('list_id'));
        $col = '12';
        $list_has_items = false;
        $move_to_basket = true;

        $response = $this->getEditList($list,$col,$list_has_items,$move_to_basket);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/get-list-modal', name: 'inventory_get_list_modal')]
    public function clinicsListModalAction(Request $request): Response
    {
        $distributor_products = $this->em->getRepository(DistributorProducts::class)->findBy([
            'product' => $request->request->get('product_id')
        ]);

        $response = '
        <!-- Modal Distributor List -->
        <div class="modal fade" id="modal_list_distributors" tabindex="-1" aria-labelledby="list_distributors_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="list_distributors_label">
                            Available Distributors
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-0">
                                <select class="form-control" id="list_distributor_id">
                                    <option value="">Select a distributor... ;</option>';

                                foreach($distributor_products as $distributor_product){

                                    $response .= '
                                    <option value="'. $distributor_product->getDistributor()->getId() .'">
                                        '. $distributor_product->getDistributor()->getDistributorName() .' 
                                        ;$'. number_format($distributor_product->getUnitPrice(),2) .'
                                    </option>';
                                }

                                $response .= '                            
                                </select>   
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">CANCEL</button>
                        <button 
                            type="button" 
                            class="btn btn-primary btn-sm" 
                            id="save_list_distributor"
                            data-id="'. $request->request->get('product_id') .'"
                            data-value="'. $request->request->get('list_id') .'"
                        >SAVE</button>
                    </div>
                </div>
            </div>
        </div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/list/update-qty', name: 'inventory_list_update_qty')]
    public function clinicsListUpdateQtyAction(Request $request): Response
    {
        $list_item = $this->em->getRepository(ListItems::class)->find($request->request->get('list_item_id'));
        $response['list_id'] = 0;

        if($list_item != null){

            $list_item->setQty($request->request->get('qty'));

            $this->em->persist($list_item);
            $this->em->flush();

            $response['list_id'] = $list_item->getList()->getId();
        }

        $response['flash'] = '
        <b><i class="fas fa-check-circle"></i> 
        Shopping list successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/list/remove-item', name: 'list_remove_item')]
    public function clinicsListRemoveItemAction(Request $request): Response
    {
        $item_id = $request->request->get('list_item_id');
        $item = $this->em->getRepository(ListItems::class)->find($item_id);
        $list_id = $item->getList()->getId();
        $col = '12';
        $list_has_items = false;
        $move_to_basket = true;

        if($item != null){

            $this->em->remove($item);
            $this->em->flush();
        }

        $list = $this->em->getRepository(Lists::class)->getIndividualList($list_id);
        $response = $this->getEditList($list,$col,$list_has_items,$move_to_basket);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/list/basket/add', name: 'list_add_to_basket')]
    public function clinicsListAddToBasketAction(Request $request): Response
    {
        $data = $request->request;
        $list_id = $data->get('list_id');
        $clinic_id = $data->get('clinic_id');
        $clear_basket = $data->get('clear_basket');

        $list = $this->em->getRepository(Lists::class)->getIndividualList($list_id);
        $basket = $this->em->getRepository(Baskets::class)->findOneBy([
            'clinic' => $clinic_id,
            'isDefault' => 1,
            'status' => 'active'

        ]);

        // Clear Basket
        if($clear_basket == 1){

            foreach($basket->getBasketItems() as $item){

                $basket_item = $this->em->getRepository(BasketItems::class)->find($item->getId());
                $this->em->remove($basket_item);
            }

            $this->em->flush();
        }

        foreach($list[0]->getListItems() as $item){

            $basket_item = new BasketItems();
            $product = $this->em->getRepository(Products::class)->find($item->getProduct());
            $distributor = $this->em->getRepository(Distributors::class)->find($item->getDistributor());

            $basket_item->setBasket($basket);
            $basket_item->setProduct($product);
            $basket_item->setDistributor($distributor);
            $basket_item->setName($item->getName());
            $basket_item->setQty($item->getQty());
            $basket_item->setUnitPrice($product->getUnitPrice());
            $basket_item->setTotal($item->getQty() * $product->getUnitPrice());

            $this->em->persist($basket_item);
        }

        $this->em->flush();

        return new JsonResponse($basket->getId());
    }

    private function getListRow($icon, $list_name, $list_id, $item_count, $keyword = ''){

        if($item_count){

            $link = '<a href="" data-keyword-string="'. $keyword .'" class="float-end view-list" data-list-id="'. $list_id .'">View List</a>';

        } else {

            $link = '<span class="float-end view-list disabled">View List</span>';
        }

        return '
                <div class="row p-2">
                    <div class="col-8 col-sm-10 ps-1 d-flex flex-column">
                        <table style="height: 30px;">
                            <tr>
                                <td class="align-middle" width="50px">
                                    '. $icon .'
                                </td>
                                <td class="align-middle info">
                                    '. $list_name .'
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-4 col-sm-2">
                        '. $link .'
                    </div>
                </div>
            ';
    }

    private function listCreateNew($product_id, $keyword = '')
    {
        return '
            <div class="row mt-4">
                <div class="col-12 col-sm-6">
                    <form name="form_list" id="form_list" method="post">
                        <input type="hidden" name="product_id" value="'. $product_id .'">
                        <input type="hidden" name="list_id" value="0">
                        <input type="hidden" name="list_type" value="custom">
                        <div class="row">
                            <div class="col-12 col-sm-8">
                                <input type="text" name="list_name" id="list_name" class="form-control mb-3 mb-sm-0">
                                <div class="hidden_msg" id="error_list_name">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <button type="submit" class="btn btn-primary mb-3 mb-sm-0 w-100 w-sm-100" id="list_create_new">
                                    <i class="fa-solid fa-circle-plus"></i>
                                    &nbsp;CREATE NEW
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-12 col-sm-6">
                    <a href="" data-keyword-string="'. $keyword .'" class="btn btn-secondary float-end w-100 w-sm-100 manage-lists">
                        VIEW AND MANAGE YOUR LISTS 
                    </a>
                </div>
            </div>';
    }

    private function getEditList($list,$col,$list_has_items,$move_to_basket)
    {
        if(count($list[0]->getListItems()) > 0) {

            $col = '9';
            $list_has_items = true;
        }

        $html = '
        <div class="row">
            <div class="col-12 col-100 border-left border-right border-bottom bg-light">
                <!-- Basket Name -->
                <div class="row">
                    <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3" id="basket_header">
                        <h4 class="text-white">'. $list[0]->getName() .'</h4>
                        <span class="text-white">
                            Manage All Your Shopping Carts In One Place
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 bg-light border-left border-right border-bottom">
                <div class="col-12 pt-2 pb-2">
                    <input 
                        type="text" 
                        id="inventory_search" 
                        class="form-control" 
                        placeholder="Inventory Item" 
                        autocomplete="off" 
                        data-list-id="'. $list[0]->getId() .'"
                    />
                    <div id="suggestion_field" style="display: none"></div>
                </div>
            </div>
            <!-- Left Column -->
            <div class="col-12 col-lg-'. $col .'">';

            $total = 0;

            if(count($list[0]->getListItems()) > 0) {

                foreach ($list[0]->getListItems() as $item){

                    $sub_total = $item->getDistributorProduct()->getUnitPrice() * $item->getQty();
                    $total += $sub_total;

                    $html .= '
                    <div class="row">
                        <div class="col-12 pt-3 pb-3 bg-light border-right">
                            <!-- Product Name and Qty -->
                            <div class="row">
                                <!-- Thumbnail -->
                                <div class="col-12 col-sm-1 col-md-12 col-lg-1 text-center pt-3 pb-3 mt-3 mt-sm-0">
                                    <img class="img-fluid basket-img" src="/images/products/'. $item->getProduct()->getImage() .'">
                                </div>
                                <!-- Product Name -->
                                <div class="col-12 col-sm-7 col-md-12 col-lg-7 pt-3 pb-3 text-center text-sm-start d-table">
                                    <div class="d-table-cell align-bottom">
                                        <span class="info">'. $item->getDistributor()->getDistributorName() .'</span>
                                        <h6 class="fw-bold text-primary lh-base mb-0">
                                            '. $item->getProduct()->getName() . ': ' . $item->getProduct()->getDosage() . ' ' . $item->getProduct()->getUnit() .'
                                        </h6>
                                    </div>
                                </div>
                                <!-- Product Quantity -->
                                <div class="col-12 col-sm-4 col-md-12 col-lg-4 pt-3 pb-3 d-table">
                                    <div class="row d-table-cell align-bottom">
                                        <div class="col-4 text-center text-sm-end text-md-start text-lg-start d-table-cell align-bottom">
                                            $'. number_format($item->getDistributorProduct()->getUnitPrice(),2) .'
                                        </div>
                                        <div class="col-4 d-table-cell">
                                            <input 
                                                type="text" 
                                                list="qty_list_'. $item->getId() .'" 
                                                data-list-item-id="'. $item->getId() .'" 
                                                name="qty" 
                                                class="form-control form-control-sm shopping-list-qty" 
                                                value="'. $item->getQty() .'" 
                                                ng-value="1"
                                            >
                                            <datalist class="datalist" id="qty_list_'. $item->getId() .'">
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
                                            <div class="hidden_msg" id="error_qty_'. $item->getId() .'"></div>
                                        </div>
                                        <div class="col-4 text-center text-sm-start text-md-end fw-bold d-table-cell align-bottom">
                                            $'. number_format($sub_total,2) .'
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Item Actions -->
                            <div class="row">
                                <div class="col-12">
                                    <!-- In Stock -->';
                                    if($item->getDistributorProduct()->getStockCount() == 0){

                                        $string = 'Out of Stock';
                                        $colour = 'danger';
                                        $move_to_basket = false;

                                    } elseif($item->getDistributorProduct()->getStockCount() < $item->getQty()) {

                                        $string = 'Only '. $item->getDistributorProduct()->getStockCount() .' In Stock';
                                        $colour = 'warning';
                                        $move_to_basket = false;

                                    } else {

                                        $string = 'In Stock';
                                        $colour = 'success';
                                    }

                                    $html .= '
                                    <span class="badge bg-'. $colour .' me-0 me-sm-2 badge-'. $colour .'-filled-sm stock-status">'. $string .'</span>
                                    <!-- Shipping Policy -->
                                    <span 
                                        class="badge bg-dark-grey badge-pending-filled-sm" 
                                        data-bs-trigger="hover" 
                                        data-bs-container="body" 
                                        data-bs-toggle="popover" 
                                        data-bs-placement="top" 
                                        data-bs-html="true" 
                                        data-bs-content="'. $item->getDistributor()->getShippingPolicy() .'" 
                                    >
                                        Shipping Policy
                                    </span>
                                    
                                    <!-- Remove Item -->
                                    <span class="badge bg-danger float-end badge-danger-filled-sm remove-list-item">
                                        <a 
                                            href="#" 
                                            class="remove-list-item text-white" 
                                            data-item-id="'. $item->getId() .'"
                                        >Remove</a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>';
                }

            } else {

                $html .= '
                <div class="row">
                    <div class="col-md-12 text-center pt-5 pb-5 col-100 border-left border-right border-top border-bottom bg-light">
                        There are currently no items in this shopping list. 
                        Please use the search above to add items to this list
                    </div>
                </div>';
            }

            $html .= '
            </div>';

            if($list_has_items) {

                $html .= '
                    <!-- Right Column -->
                    <div class="col-12 col-lg-3 pt-3 pb-3 bg-light border-right col-cell">
                        <div class="row">
                            <div class="col-12 text-truncate ps-0 ps-sm-2">
                                <span class="info">Subtotal:</span>
                                <h5 class="d-inline-block text-primary float-end">$' . number_format($total, 2) . '</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 info ps-0 ps-sm-2">
                                Shipping: <span class="float-end fw-bold">$6.99</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 pt-4 text-center ps-0 ps-sm-2">';

                if($move_to_basket){

                    $html .= '
                    <a 
                        href="" 
                        class="btn btn-primary w-100" 
                        id="btn_list_add_to_basket" 
                        data-list-id="'. $item->getList()->getId() .'"
                        data-bs-toggle="modal"
                        data-bs-target="#modal_list_add_to_basket"
                    >
                        ADD TO BASKET <i class="fa-solid fa-circle-plus ps-2"></i>
                    </a>';

                } else {

                    $html .= '
                    <span 
                        class="btn btn-primary 
                        w-100 btn-disabled" 
                        style="cursor: text"
                    >
                        ADD TO BASKET <i class="fa-solid fa-circle-plus ps-2"></i>
                    </span>';
                }

                $html .= '
                        </div>
                    </div>
                </div>';

            }

            $html .= '
            </div>
            
            <!-- Modal Add To Basket -->
            <div class="modal fade" id="modal_list_add_to_basket" tabindex="-1" aria-labelledby="save_basket_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header basket-modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <h6>Clear current basket?</h6>
                                    Would you like to clear your Fluid Commerc basket before adding the shopping list items to it?
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button 
                                class="btn btn-primary btn-sm list-add-basket" 
                                name="list_basket_save_clear" 
                                data-basket-clear="1"
                                data-list-id="'. $list[0]->getId() .'"
                                data-clinic-id="'. $this->getUser()->getClinic()->getId() .'"
                            >CLEAR AND ADD</button>
                            <button 
                                class="btn btn-danger btn-sm list-add-basket" 
                                name="list_basket_save" 
                                data-basket-clear="0"
                                data-list-id="'. $list[0]->getId() .'"
                                data-clinic-id="'. $this->getUser()->getClinic()->getId() .'"
                            >ADD</button>
                        </div>
                    </div>
                </div>
            </div>';

        $response = [

            'flash' => '<b><i class="fas fa-check-circle"></i> 
                        Shopping list successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'html' => $html
        ];

        return $response;
    }
}
