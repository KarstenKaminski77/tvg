<?php

namespace App\Controller;

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
                    class="delete-list float-end me-3 text-danger delete-list"
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
                <div class="col-10">
                    <b>'. $list->getName() .' ('. $list->getListItems()->count() .')</b><br>
                    Fluid '. $list_type .' List
                </div>
                <div class="col-2">
                    <a 
                        href="#" 
                        class="view-list float-end text text-success view-list"
                        data-list-id="'. $list->getId() .'"
                        data-keyword-string="'. $request->request->get('keyword') .'"
                        data-page-id="0"
                    >
                        <i class="fa-solid fa-eye"></i>
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
        $list_item->setName($products->getName());

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
                    dump($lists[$i]->getListItems()[$c]->getProduct()->getId(),$product_id);
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
                    dump($lists[$i]->getListItems()[$c]->getProduct()->getId(),$product_id);
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
}
