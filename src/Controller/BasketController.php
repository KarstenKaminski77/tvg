<?php

namespace App\Controller;

use App\Entity\BasketItems;
use App\Entity\Baskets;
use App\Entity\ClinicProducts;
use App\Entity\Clinics;
use App\Entity\ClinicUsers;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\Products;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BasketController extends AbstractController
{
    public function __construct(EntityManagerInterface $entityManager, PaginationManager $pagination)
    {
        $this->page_manager = $pagination;
        $this->em = $entityManager;
    }

    #[Route('/clinics/inventory/inventory-add-to-basket', name: 'inventory_add_to_basket')]
    public function addToBasketAction(Request $request): Response
    {
        $distributor_id = $request->get('distributor_id');

        $product_id = $request->get('product_id');
        $qty = $request->get('qty');
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $distributor = $this->em->getRepository(Distributors::class)->find($distributor_id);
        $product = $this->em->getRepository(Products::class)->find($product_id);
        $distributor_products = $this->em->getRepository(DistributorProducts::class)->findBy([
            'product' => $product_id,
            'distributor' => $distributor_id
        ]);
        $basket = $this->em->getRepository(Baskets::class)->findOneBy([
            'clinic' => $this->getUser()->getClinic()->getId(),
            'status' => 'active',
            'isDefault' => 1,
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

        $qty_error = '';

        if($distributor_products[0]->getStockCount() < $qty){

            $qty = $distributor_products[0]->getStockCount();
            $qty_error = 'Only '. $qty .' units in stock, please select '. $qty .' or less';

            $response = [
                'product_id' => $product->getId(),
                'distributor_id' => $distributor->getId(),
                'error' => $qty_error,
            ];

            return new JsonResponse($response);
        }

        $basket_item->setBasket($basket);
        $basket_item->setDistributor($distributor);
        $basket_item->setProduct($product);
        $basket_item->setName($product->getName());
        $basket_item->setQty($qty);
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
            'error' => $qty_error,
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/inventory-update-basket', name: 'inventory_update_basket')]
    public function updateBasketAction(Request $request): Response
    {
        $item_id = $request->request->get('item_id');
        $basket_item = $this->em->getRepository(BasketItems::class)->find($item_id);
        $basket_id = $basket_item->getBasket()->getId();
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);

        if($basket_item != null){

            $product_id = $basket_item->getProduct()->getId();
            $distributor_id = $basket_item->getDistributor()->getId();
            $qty = (int) $request->request->get('qty');

            $distributor_products = $this->em->getRepository(DistributorProducts::class)->findBy([
                'product' => $product_id,
                'distributor' => $distributor_id,
            ]);

            if($distributor_products[0]->getStockCount() < $qty){

                $qty = $distributor_products[0]->getStockCount();
                $qty_error = 'Only '. $qty .' units in stock, please select '. $qty .' or less';

                $response = [
                    'error' => $qty_error,
                    'message' => '',
                    'basket_id' => $basket_id,
                    'item_id' => $item_id,
                ];

                return new JsonResponse($response);
            }

            $basket_item->setQty($qty);
            $basket_item->setTotal($basket_item->getUnitPrice() * $qty);

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
            'error' => '',
            'message' => '<b><i class="fas fa-check-circle"></i> '. $basket_item->getProduct()->getName() .' updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_id,
            'item_id' => '',
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

        if($basket->getBasketItems()->count() > 0){

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

    #[Route('/clinics/inventory/save-item', name: 'save_item')]
    public function saveItemAction(Request $request): Response
    {
        $product = $this->em->getRepository(Products::class)->find($request->request->get('product_id'));
        $distributor = $this->em->getRepository(Distributors::class)->find($request->request->get('distributor_id'));
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $basket_items = $this->em->getRepository(BasketItems::class)->find($request->request->get('item_id'));
        $basket_id = $basket_items->getBasket()->getId();
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $create = false;

        // Ensure item has not already been saved
        $clinic_products = $this->em->getRepository(ClinicProducts::class)->findOneBy([
            'clinic' => $clinic,
            'product' => $product,
            'distributor' => $distributor
        ]);

        if($clinic_products == null) {

            $clinic_products = new ClinicProducts();
            $create = true;
        }

        $clinic_products->setProduct($product);
        $clinic_products->setDistributor($distributor);
        $clinic_products->setClinic($clinic);
        $clinic_products->setName($basket_items->getName());
        $clinic_products->setQty($basket_items->getQty());
        $clinic_products->setUnitPrice($basket_items->getUnitPrice());
        $clinic_products->setTotal($basket_items->getQty() * $basket_items->getUnitPrice());
        $clinic_products->setSavedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

        $this->em->persist($clinic_products);
        $this->em->flush();

        $this->em->remove($basket_items);
        $this->em->flush();

        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket_id);
        $basket_items = $this->em->getRepository(BasketItems::class)->findBy([
            'basket' => $basket_id
        ]);

        if(count($basket_items) > 0){

            $basket->setTotal(number_format($totals[0]['total'],2));

        } else {

            $basket->setTotal(0,2);
        }

        $this->em->persist($basket);
        $this->em->flush();

        if($create){

            $message = '<b><i class="fas fa-check-circle"></i> '. $product->getName() .'</b> saved for later.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $message = '<b><i class="fas fa-check-circle"></i> '. $product->getName() .'</b> has already been saved for later.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $response = [
            'message' => $message,
            'basket_id' => $basket_id
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/save-all-items', name: 'save_all_items')]
    public function saveAllItemAction(Request $request): Response
    {
        $basket_id = $request->request->get('basket_id');
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $basket_items = $this->em->getRepository(BasketItems::class)->findBy(['basket' => $basket_id]);

        foreach($basket_items as $item){

            $item_id = $item->getId();
            $clinic_products = $this->em->getRepository(ClinicProducts::class)->findOneBy([
                'product' => $item->getProduct(),
                'distributor' => $item->getDistributor(),
                'clinic' => $clinic,
            ]);

            if($clinic_products == null) {

                $clinic_products = new ClinicProducts();

            }

            $clinic_products->setClinic($clinic);
            $clinic_products->setDistributor($item->getDistributor());
            $clinic_products->setProduct($item->getProduct());
            $clinic_products->setName($item->getName());
            $clinic_products->setQty($item->getQty());
            $clinic_products->setUnitPrice($item->getUnitPrice());
            $clinic_products->setTotal($item->getQty() * $item->getUnitPrice());
            $clinic_products->setSavedBy($this->getUser()->getFirstName() .' '. $this->getUser()->getLastName());

            $this->em->persist($clinic_products);
            $this->em->flush();

            $basket_item = $this->em->getRepository(BasketItems::class)->find($item->getId());

            $this->em->remove($basket_item);
            $this->em->flush();
        }

        $response = [
            'message' => '<b><i class="fas fa-check-circle"></i></b> All items saved for later.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_id
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/restore-item', name: 'restore_item')]
    public function restoreItemAction(Request $request): Response
    {
        $product = $this->em->getRepository(Products::class)->find($request->request->get('product_id'));
        $distributor = $this->em->getRepository(Distributors::class)->find($request->request->get('distributor_id'));
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $clinic_products = $this->em->getRepository(ClinicProducts::class)->find($request->request->get('item_id'));
        $basket = $this->em->getRepository(Baskets::class)->find($request->request->get('basket_id'));

        $basket_item = new BasketItems();

        $basket_item->setProduct($product);
        $basket_item->setDistributor($distributor);
        $basket_item->setBasket($basket);
        $basket_item->setName($product->getName());
        $basket_item->setQty($clinic_products->getQty());
        $basket_item->setUnitPrice($clinic_products->getUnitPrice());
        $basket_item->setTotal($clinic_products->getTotal());

        $this->em->persist($basket_item);
        $this->em->flush();

        $this->em->remove($clinic_products);
        $this->em->flush();

        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket->getId());
        $basket_items = $this->em->getRepository(BasketItems::class)->findBy([
            'basket' => $basket->getId(),
        ]);

        if(count($basket_items) > 0){

            $basket->setTotal((float) $totals[0]['total']);

        } else {

            $basket->setTotal(0,2);
        }

        $this->em->persist($basket);
        $this->em->flush();

        $response = [
            'message' => '<b><i class="fas fa-check-circle"></i> '. $product->getName() .'</b> moved to basket.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket->getId()
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/restore-all-items', name: 'restore_all_items')]
    public function restoreAllItemsAction(Request $request): Response
    {
        $basket_id = $request->request->get('basket_id');
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $clinic_products = $this->em->getRepository(ClinicProducts::class)->findBy([
            'clinic' => $this->getUser()->getClinic()
        ]);

        foreach($clinic_products as $product){

            $basket_item = new BasketItems();

            $basket_item->setBasket($basket);
            $basket_item->setProduct($product->getProduct());
            $basket_item->setDistributor($product->getDistributor());
            $basket_item->setName($product->getName());
            $basket_item->setQty($product->getQty());
            $basket_item->setUnitPrice($product->getUnitPrice());
            $basket_item->setTotal($product->getQty() * $product->getUnitPrice());

            $this->em->persist($basket_item);
            $this->em->flush();

            $remove_basket = $this->em->getRepository(ClinicProducts::class)->find($product->getId());

            $this->em->remove($remove_basket);
            $this->em->flush();
        }

        $totals = $this->em->getRepository(BasketItems::class)->getTotalItems($basket_id);
        $basket_items = $this->em->getRepository(BasketItems::class)->findBy([
            'basket' => $basket_id,
        ]);

        if(count($basket_items) > 0){

            $basket->setTotal(number_format($totals[0]['total'],2));

        } else {

            $basket->setTotal(0,2);
        }

        $this->em->persist($basket);
        $this->em->flush();

        $response = [
            'message' => '<b><i class="fas fa-check-circle"></i></b> All saved items moved to basket.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_id
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/remove-saved-item', name: 'remove_saved_item')]
    public function removeSavedItemAction(Request $request): Response
    {
        $item = $this->em->getRepository(ClinicProducts::class)->find($request->request->get('item_id'));
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $product = $item->getProduct()->getName();

        $this->em->remove($item);
        $this->em->flush();

        $basket = $this->em->getRepository(Baskets::class)->findOneBy([
            'clinic' => $clinic,
            'name' => 'Fluid Commerce',
            'status' => 'active'
        ]);

        $response = [
            'message' => '<b><i class="fas fa-check-circle"></i> '. $product .'</b> removed.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket->getId(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/save-basket', name: 'save_basket')]
    public function saveBasketAction(Request $request): Response
    {
        $basket_name = $request->request->get('basket_name');
        $basket_id = $request->request->get('basket_id');
        $saved_by = $this->setSavedBy();
        $clinic = $this->em->getRepository(Clinics::class)->find($this->getUser()->getClinic()->getId());
        $basket_items = $this->em->getRepository(BasketItems::class)->findBy(['basket' => $basket_id]);
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $create = false;

        // Create new basket
        $basket_new = new Baskets();

        $basket_new->setName($basket_name);
        $basket_new->setClinic($clinic);
        $basket_new->setTotal($basket->getTotal());
        $basket_new->setSavedBy($saved_by);
        $basket_new->setStatus('active');
        $basket_new->setIsDefault(0);

        $this->em->persist($basket_new);
        $this->em->flush();

        foreach($basket_items as $item){

            $basket_items_new = new BasketItems();
            $product = $this->em->getRepository(Products::class)->find($item->getProduct());
            $distributor = $this->em->getRepository(Distributors::class)->find($item->getDistributor());

            $basket_items_new->setBasket($basket_new);
            $basket_items_new->setProduct($product);
            $basket_items_new->setDistributor($distributor);
            $basket_items_new->setName($item->getName());
            $basket_items_new->setQty($item->getQty());
            $basket_items_new->setUnitPrice($item->getUnitPrice());
            $basket_items_new->setTotal($item->getQty() * $item->getUnitPrice());

            $this->em->persist($basket_items_new);
            $this->em->flush();
        }

        // Clear Basket
        if((int) $request->request->get('clear') == 1){

            $basket->setTotal('0.00');
            $basket->setSavedBy($saved_by);

            foreach($basket_items as $item){

                $basket_item = $this->em->getRepository(BasketItems::class)->find($item->getId());

                $this->em->remove($basket_item);
                $this->em->flush();
            }
        }

        $response = [
            'message' => '<b><i class="fas fa-check-circle"></i> '. $product->getName() .'</b> saved for later.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'basket_id' => $basket_new->getId()

        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/get-saved-baskets', name: 'get_saved_baskets')]
    public function getSavedBasketsAction(Request $request): Response
    {
        $response = $this->getSavedbasketsRightColumn();

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/get-saved-basket-details', name: 'get_saved_basket_details')]
    public function getSavedBasketDetailsAction(Request $request): Response
    {
        $saved_basket = $this->em->getRepository(BasketItems::class)->findBy(
            [
                'basket' => $request->request->get('basket_id')
            ]);

        if(count($saved_basket) > 0) {

            $response = '
            <div class="row border-bottom-dashed">
                <div class="col-3 pt-3 pb-3">
                    <b>Name</b>
                </div>
                <div class="col-3 pt-3 pb-3">
                    <b>Unit Price</b>
                </div>
                <div class="col-2 pt-3 pb-3">
                   <b>Quantity</b>
                </div>
                <div class="col-2 pt-3 pb-3">
                    <b>Total Price</b>
                </div>
                <div class="col-2 pt-3 pb-3" style="padding-top: 3px">
                    <b>Status</b>
                </div>
            </div>';

            $i = 0;

            foreach ($saved_basket as $basket) {

                $stock_count = $basket->getProduct()->getDistributorProducts()[0]->getStockCount();

                $status = 'Out Of Stock';

                if($stock_count > 1){

                    $status = $stock_count . ' In Stock';
                }

                $i++;
                $unit_price = $basket->getProduct()->getDistributorProducts()[0]->getUnitPrice();

                $response .= '
                <div class="row border-bottom-dashed">
                    <div class="col-3 pt-3 pb-3">
                        '. $basket->getProduct()->getName() .' '. $basket->getProduct()->getDosage() .' '. $basket->getProduct()->getUnit() .'
                    </div>
                    <div class="col-3 pt-3 pb-3">
                        $' . number_format($unit_price, 2) . '
                    </div>
                    <div class="col-2 pt-3 pb-3">
                       ' . $basket->getQty() . '
                    </div>
                    <div class="col-2 pt-3 pb-3">
                        $' . number_format($unit_price * $basket->getQty(), 2) . '
                    </div>
                    <div class="col-2 pt-3 pb-3" style="padding-top: 3px">
                        '. $status .'
                    </div>
                </div>';
            }
        } else {

            $response = '
            <div class="row border-bottom-dashed">
                <div class="col-12 pt-3 pb-3 text-center">
                    <p></p>
                    <h5>Your basket at Fluid Commerce is currently empty </h5><br>
                    Were you expecting to see items here? View copies of the items most recently added<br> 
                    to your basket and restore a basket if needed.
                    <p></p>
                </div>
            </div>';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/update-saved-baskets', name: 'update_saved_baskets')]
    public function updateSavedBasketsAction(Request $request): Response
    {
        $basket = $this->em->getRepository(Baskets::class)->find($request->request->get('basket_id'));
        $basket->setName($request->request->get('basket_name'));

        $this->em->persist($basket);
        $this->em->flush();

        $response = $this->getBasketLeftColumn($request);

        return new JsonResponse($response);
    }

    private function getSavedbasketsRightColumn()
    {
        $saved_baskets = $this->em->getRepository(Baskets::class)->findBy([
            'clinic' => $this->getUser()->getClinic()->getId(),
            'status' => 'active'
        ]);


        $response = '
        <!-- Basket Name -->
        <div class="row">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3">
                <h4 class="text-white">Saved Shopping Baskets</h4>
                <span class="text-white">
                    Manage Previously Saved Shopping Baskets
                </span>
            </div>
        </div>';

        if(count($saved_baskets) > 1) {

            $response .= '
            <!-- Basket Items -->
            <div class="row border-bottom bg-secondary d-none d-sm-flex">
                <div class="col-3 pt-3 pb-3">
                    <h6 class="text-primary m-0" style="padding-top: 3px">Basket Name</h6>
                </div>
                <div class="col-3 pt-3 pb-3">
                    <h6 class="text-primary m-0" style="padding-top: 3px">Saved By</h6>
                </div>
                <div class="col-2 pt-3 pb-3">
                    <h6 class="text-primary m-0" style="padding-top: 3px">Subtotal</h6>
                </div>
                <div class="col-2 pt-3 pb-3">
                    <h6 class="text-primary m-0" style="padding-top: 3px">Items</h6>
                </div>
                <div class="col-2 pt-3 pb-3" style="padding-top: 3px"></div>
            </div>';

            foreach ($saved_baskets as $basket) {

                if ($basket->getName() != 'Fluid Commerce') {

                    $response .= '
                    <!-- Saved Baskets -->
                    <div class="row border-bottom-dashed saved_basket_header" role="button" id="saved_basket_header_' . $basket->getId() . '">
                        <div class="col-3 pt-3 pb-3 saved-basket-link" id="saved_basket_first_' . $basket->getId() . '" data-basket-id="' . $basket->getId() . '">
                            <span id="basket_name_string_' . $basket->getId() . '">' . $basket->getName() . '</span>
                            <span id="basket_name_input_' . $basket->getId() . '" style="display:none"><input type="text" class="form-control form-control-sm" id="basket_name_' . $basket->getId() . '" value="' . $basket->getName() . '"></span>
                        </div>
                        <div class="col-3 pt-3 pb-3 saved-basket-link" data-basket-id="' . $basket->getId() . '">
                            ' . $basket->getSavedBy() . '
                        </div>
                        <div class="col-2 pt-3 pb-3 saved-basket-link" data-basket-id="' . $basket->getId() . '">
                           ' . number_format($basket->getTotal(), 2) . '
                        </div>
                        <div class="col-2 pt-3 pb-3 saved-basket-link" data-basket-id="' . $basket->getId() . '">
                            ' . $basket->getBasketItems()->count() . '
                        </div>
                        <div class="col-2 pt-3 pb-3">
                            <a href="" class="basket-edit" data-basket-id="' . $basket->getId() . '"><i class="fa-solid fa-pencil float-end me-3"></i></a>
                            <a href="" class="basket-delete" data-basket-id="' . $basket->getId() . '"><i class="fa-solid fa-trash-can text-danger float-end me-4"></i></a>
                        </div>
                    </div>
                    <!-- Baskets -->
                    <div class="saved-basket-panel" id="saved_basket_panel_' . $basket->getId() . '" style="display: none">This basket is empty...</div>';
                }
            }

        } else {

            $response .= '
            <div class="row border-bottom d-none d-sm-flex">
                <div class="col-12 pt-3 pb-3 text-center">
                    <p></p>
                    <h5>You don\'t currently have any saved baskets</h5><br>
                    Were you expecting to see items here? View copies of the items most recently added<br> 
                    to your basket and restore a basket if needed.
                    <p></p>
                </div>
            </div>
            ';
        }

        return $response;
    }

    private function getBasketLeftColumn($request)
    {
        $clinic_id = $this->getUser()->getClinic()->getId();
        $baskets = $this->em->getRepository(Baskets::class)->findBy([
            'clinic' => $clinic_id
        ]);
        $clinic_totals = $this->em->getRepository(Baskets::class)->getClinicTotalItems($clinic_id);
        $total_clinic = number_format($clinic_totals[0]['total'] ?? 0,2);
        $count_clinic = $clinic_totals[0]['item_count'] ?? 0;

        $response = '
        <div class="row border-bottom text-center pt-2 pb-2">
            <b>All Baskets</b>
        </div>
        <div class="row" style="background: #f4f8fe">
            <div class="col-6 border-bottom pt-1 pb-1 text-center">
                <span class="d-block text-primary">'. $count_clinic .'</span>
                <span class="d-block text-truncate">Items</span>
            </div>
            <div class="col-6 border-bottom pt-1 pb-1 text-center">
                <span class="d-block text-primary">$'. number_format($total_clinic,2) .'</span>
                <span class="d-block text-truncate">Subtotal</span>
            </div>
        </div>';

        foreach($baskets as $basket) {

            $active = '';
            $background = '';

            if($basket->getId() == $request->request->get('basket_id')){

                $active = 'active-basket';
            }

            if($basket->getBasketItems()->count() > 0){

                $background = 'bg-primary';
            }

            $response .= '
            <div class="row">
                <div class="col-12 border-bottom '. $active .'">
                    <a href="#" data-basket-id="'. $basket->getId() .'" class=" pt-3 pb-3 d-block basket-link">
                        <span class="d-inline-block align-baseline">'. $basket->getName() .'</span>
                        <span class="float-end basket-item-count-empty '. $background .'">
                            '. $basket->getBasketItems()->count() .'
                        </span>
                    </a>
                </div>
            </div>';
        }

        $response .= '
        <div class="row border-bottom">
            <div class="col-4 col-sm-12 col-md-4 pt-3 pb-3 saved-baskets">
                <i class="fa-solid fa-basket-shopping"></i>
            </div>
            <div class="col-8 col-sm-12 col-md-8 pt-3 pb-3">
                <h6 class="text-primary">Saved Baskets</h6>
                <span class="info">View baskets</span>
            </div>
        </div>';

        return $response;
    }

    #[Route('/clinics/inventory/delete-saved-basket', name: 'delete_saved_basket')]
    public function deleteBasketAction(Request $request): Response
    {
        $basket_id = $request->request->get('basket_id');
        $basket_items = $this->em->getRepository(BasketItems::class)->findBy(['basket' => $basket_id]);
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $baskets= $this->em->getRepository(Baskets::class)->findBy([
            'clinic' => $this->getUser()->getClinic()->getId()
        ]);

        if(count($basket_items) > 0){

            foreach($basket_items as $item){

                $this->em->remove($item);
                $this->em->flush();
            }
        }

        $this->em->remove($basket);
        $this->em->flush();

        $response = [
            'left_col' => $this->getBasketLeftColumn($request),
            'right_col' => $this->getSavedbasketsRightColumn(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/get/basket', name: 'get_basket')]
    public function getBasketAction(Request $request): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $clinic_id = $this->getUser()->getClinic()->getId();
        $basket_id = $request->request->get('basket_id') ?? $request->get('basket_id');
        //dd($request->request->get('basket_id'), $request->get('basket_id'));
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $baskets = $this->em->getRepository(Baskets::class)->findActiveBaskets($clinic_id);
        $clinic_totals = $this->em->getRepository(Baskets::class)->getClinicTotalItems($clinic_id);
        $saved_items = $this->em->getRepository(ClinicProducts::class)->findBy([
            'clinic' => $this->getUser()->getClinic()->getId()
        ]);

        $total_clinic = number_format($clinic_totals[0]['total'] ?? 0,2);
        $count_clinic = $clinic_totals[0]['item_count'] ?? 0;

        $response = '
        <div class="row">
            <!-- Left Column -->
            <div class="col-12 col-md-2 col-100" id="basket_left_col">
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
                </div>';

        foreach($baskets as $individual_basket){

            $count = $individual_basket->getBasketItems()->count();
            $bg_primary = '';
            $active = '';

            if($count > 0){

                $bg_primary = 'bg-primary';
            }

            if($individual_basket->getId() == $basket_id){

                $active = 'active-basket';
            }

            $response .= '
            <div class="row">
                <div class="col-12 border-bottom '. $active .'">
                    <a href="#" data-basket-id="'. $individual_basket->getId() .'" class=" pt-3 pb-3 d-block basket-link">
                        <span class="d-inline-block align-baseline">'. $individual_basket->getName() .'</span>
                        <span class="float-end basket-item-count-empty '. $bg_primary .'">
                            '. $count .'
                        </span>
                    </a>
                </div>
            </div>';
        }

        $response .= '
            <div class="row border-bottom">
                <div class="col-12 h-100">
                    <a href="#" class="saved_baskets_link" data-basket-id="'. $basket_id .'">
                        <div class="row align-items-center">
                            <div class="col-4 col-sm-12 col-md-4 pt-3 pb-3 saved-baskets">
                                <i class="fa-solid fa-basket-shopping"></i>
                            </div>
                            <div class="col-8 col-sm-12 col-md-8 pt-3 pb-3">
                                <h6 class="text-primary">Saved Baskets</h6>
                                <span class="info">View baskets</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-12 col-md-10 col-100 border-left" id="basket_items">
            <!-- Basket Name -->
            <div class="row">
                <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3" id="basket_header">
                    <h4 class="text-white">'. $basket->getName() .' Basket</h4>
                    <span class="text-white">
                        Manage All Your Shopping Carts In One Place
                    </span>
                </div>
            </div>
            <!-- Basket Actions Upper Row -->
            <div class="row" id="basket_action_row_1">
                <div class="col-12 d-flex justify-content-center border-bottom pt-3 pb-3">
                    <a href="#" class="refresh-basket" data-basket-id="'. $basket_id .'">
                        <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i><span class=" d-none d-md-inline-block pe-4">Refresh Basket</span>
                    </a>
                    <a href="#" id="print_basket">
                        <i class="fa-solid fa-print me-5 me-md-2"></i><span class=" d-none d-md-inline-block pe-4">Print</span>
                    </a>
                    <a href="#" class="saved_baskets_link" data-basket-id="'. $basket_id .'">
                        <i class="fa-solid fa-basket-shopping me-5  me-md-2"></i><span class=" d-none d-md-inline-block pe-4">Saved Baskets</span>
                    </a>
                    <a href="#" id="return_to_search">
                        <i class="fa-solid fa-magnifying-glass me-0 me-md-2"></i><span class=" d-none d-md-inline-block pe-4">Back To Search</span>
                    </a>
                </div>
            </div>';

        if(count($basket->getBasketItems()) > 0) {

            $response .= '
            <!-- Basket Actions Lower Row -->
            <div class="row" id="basket_action_row_2">
                <div class="col-12 d-flex justify-content-center border-bottom pt-3 pb-3">
                    <a href="#" class="save-all-items" data-basket-id="' . $basket_id . '">
                        <i class="fa-regular fa-bookmark me-5 me-md-2"></i>
                        <span class=" d-none d-md-inline-block pe-4">Save All For Later</span>
                    </a>
                    <a href="#" class="clear-basket" data-basket-id="' . $basket_id . '">
                        <i class="fa-solid fa-trash-can me-5 me-md-2"></i>
                        <span class=" d-none d-md-inline-block pe-4">Clear Basket</span>
                    </a>
                    <a href="#" class="refresh-basket" data-basket-id="'. $basket_id .'">
                        <i class="fa-solid fa-arrow-rotate-right me-5 me-md-2"></i>
                        <span class=" d-none d-md-inline-block pe-4">Refresh Basket</span>
                    </a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modal_save_basket">
                        <i class="fa-solid fa-basket-shopping me-0 me-md-2"></i>
                        <span class=" d-none d-md-inline-block pe-0">Save Basket</span>
                    </a>
                </div>
            </div>';
        }

        $basket_summary = false;
        $col = '12';

        if(count($basket->getBasketItems()) > 0) {

            $basket_summary = true;
            $col = '9 border-right';
        }

        $response .= '
                <!-- Basket Items -->
                <div class="row col-container d-flex border-0 m-0">
                    <div class="col-12 col-md-'. $col .' col-cell ps-0" id="basket_inner">';

        $i = -1;
        $checkout_disabled = '';
        $checkout_btn_disabled = '';
        $checkout = true;

        if(count($basket->getBasketItems()) > 0) {

            foreach ($basket->getBasketItems() as $item) {

                $i++;
                $product = $basket->getBasketItems()[$i]->getProduct();
                $shipping_policy = $item->getDistributor()->getShippingPolicy();
                $distributor_product = $this->em->getRepository(DistributorProducts::class)->findOneBy([
                    'product' => $item->getProduct()->getId(),
                    'distributor' => $item->getDistributor()->getId(),
                ]);

                if($distributor_product->getStockCount() > 0){

                    $stock_badge = '<span class="badge bg-success me-2">In Stock</span>';
                    $disabled = '';

                } else {

                    $stock_badge = '<span class="badge bg-danger me-2">Out Of Stock</span>';
                    $disabled = 'disabled';
                    $checkout = false;
                }

                $response .= '
                <div class="row">
                    <!-- Thumbnail -->
                    <div class="col-12 col-sm-2 text-center pt-3 pb-3 mt-3">
                        <img class="img-fluid basket-img" src="/images/products/' . $product->getImage() . '">
                    </div>
                    <div class="col-12 col-sm-10 pt-3 pb-3">
                        <!-- Product Name and Qty -->
                        <div class="row">
                            <!-- Product Name -->
                            <div class="col-12 col-sm-7 pt-3 pb-3">
                                <span class="info">'. $distributor_product->getDistributor()->getDistributorName() .'</span>
                                <h6 class="fw-bold text-center text-sm-start text-primary lh-base">
                                    ' . $product->getName() . ': ' . $product->getDosage() . ' ' . $product->getUnit() . '
                                </h6>
                            </div>
                            <!-- Product Quantity -->
                            <div class="col-12 col-sm-5 pt-3 pb-3">
                                <div class="row">
                                    <div class="col-4 text-center text-sm-end">
                                        $' . number_format($item->getUnitPrice(),2) . '
                                    </div>
                                    <div class="col-4">
                                        <input 
                                            type="text" 
                                            list="qty_list_' . $product->getId() . '" 
                                            data-basket-item-id="' . $item->getId() . '" 
                                            name="qty" 
                                            class="form-control basket-qty" 
                                            value="' . $item->getQty() . '" 
                                            ng-value="' . $item->getQty() . '"
                                            '. $disabled .'
                                        >
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
                                        <div class="hidden_msg" id="stock_count_error_'. $item->getId() .'"></div>
                                    </div>
                                    <div class="col-4 text-center text-sm-start fw-bold">$' . number_format($item->getTotal(),2) . '</div>
                                </div>
                            </div>
                        </div>
                        <!-- Item Actions -->
                        <div class="row">
                            <div class="col-12">
                                <!-- In Stock -->
                                '. $stock_badge .'
                                <!-- Shipping Policy -->
                                <span class="badge bg-dark-grey" class="btn btn-secondary" data-bs-trigger="hover"
                                      data-bs-container="body" data-bs-toggle="popover" data-bs-placement="top" data-bs-html="true"
                                      data-bs-content="'. $shipping_policy .'">Shipping Policy</span>
                                <!-- Remove Item -->
                                <span class="badge bg-danger float-end">
                                    <a href="#" class="remove-item text-white" data-item-id="' . $item->getId() . '">Remove</a>
                                </span>
                                <!-- Save Item -->
                                <span class="badge badge-light float-end me-2">
                                    <a href="#" class="link-secondary save-item" data-basket-id="'. $basket_id .'" data-product-id="'. $product->getId() .'" data-distributor-id="'. $item->getDistributor()->getId() .'" data-item-id="' . $item->getId() . '">Save Item For later</a>
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
                    </div>';

        if($basket_summary) {

            $checkout_error = '';

            if(!$checkout){

                $checkout_disabled = 'disabled';
                $checkout_btn_disabled = 'btn-secondary disabled';
                $checkout_error = '
                <div class="text-danger mt-3">
                    One or more items in your basket is currently out of stock. Remove or save the item for later
                    to proceed to checkout. 
                </div>';
            }

            $response .= '
                    <!-- Basket Summary -->
                    <div class="col-12 col-md-3 pt-3 pb-3 pe-0 col-cell" id="basket_summary">
                        <div class="row">
                            <div class="col-12 text-truncate">
                                <span class="info">Subtotal:</span>
                                <h5 class="d-inline-block text-primary float-end">$' . number_format($basket->getTotal(),2) . '</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 info">
                                Shipping: <span class="float-end fw-bold">$6.99</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 pt-4 text-center">
                                <a 
                                    href="" 
                                    class="btn btn-primary w-100 '. $checkout_btn_disabled .'" 
                                    id="btn_checkout"
                                    data-basket-id="'. $basket_id .'"
                                    '. $checkout_disabled .'
                                >
                                    PROCEED TO CHECKOUT <i class="fa-solid fa-circle-right ps-2"></i>
                                </a>
                                '. $checkout_error .'
                            </div>
                        </div>
                    </div>';
        }

        $response .= '
                </div>';

        // Saved Items
        if(count($saved_items) > 0){

            $plural = '';

            if(count($saved_items) > 1){

                $plural = 's';
            }

            $response .= '
                    <div class="row" style="background: #f4f8fe" id="saved_items">
                        <div class="col-12 border-bottom border-top pt-3 pb-3">
                            <a href="" id="saved_items_link">Items Saved for Later ('. count($saved_items) .' Item'. $plural .')</a>
                        </div>
                    </div>
                    <div class="row" id="saved_items_container">
                        <div class="col-12 border-bottom border-top pt-3 pb-3 position-relative">
                            <a href="" class="btn btn-primary btn-sm restore-all" id="restore_all" data-basket-id="'. $basket_id .'">Move All To Basket</a>
                ';

            foreach($saved_items as $item){

                $product = $item->getProduct();

                $response .= '
                    <div class="row">
                        <!-- Thumbnail -->
                        <div class="col-12 col-sm-2 text-center pt-3 pb-3">
                            <img class="img-fluid basket-img" src="/images/products/' . $product->getImage() . '">
                        </div>
                        <div class="col-12 col-sm-10 pt-3 pb-3">
                            <div class="row">
                                <!-- Product Name -->
                                <div class="col-12 col-sm-7">
                                    <h6 class="fw-bold text-center text-sm-start text-primary lh-base mb-0">
                                        ' . $product->getName() . ': ' . $product->getDosage() . ' ' . $product->getUnit() . ', Each
                                    </h6>
                                    Saved on '. $item->getModified()->format('M jS Y') .' by '. $item->getSavedBy() .'<br>
                                    <span class="badge badge-light me-2 mt-2">
                                        <a href="#" class="link-secondary restore-item" data-basket-id="'. $basket_id .'" data-product-id="'. $product->getId() .'" data-distributor-id="'. $item->getDistributor()->getId() .'" data-item-id="'. $item->getId() .'">
                                            Move To Basket
                                        </a>
                                    </span>
                                    <span class="badge bg-danger mt-2">
                                        <a href="#" class="text-white remove-saved-item" data-basket-id="" data-item-id="'. $item->getId() .'">
                                            Remove
                                        </a>
                                    </span>
                                </div>
                            </div>
                       
                        </div>
                    </div>';
            }
        }

        $response .= '     
                    </div>
                </div>   
            </div>
        </div>
        <!-- Modal Save Basket -->
        <div class="modal fade" id="modal_save_basket" tabindex="-1" aria-labelledby="save_basket_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form name="form_save_basket" id="form_save_basket" method="post">
                        <input type="hidden" name="basket_id" value="'. $basket_id .'">
                        <div class="modal-header basket-modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <h6>Clear current basket?</h6>
                                    After you save this basket for later, would you like to clear this basket?
                                </div>
                                <div class="col-12 mb-0">
                                    <input type="text" class="form-control" name="basket_name" id="basket_name" placeholder="Basket Name">
                                </div>
                                <div class="hidden_msg" id="error_basket_name">
                                    Please enter name for the basket
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="submit" class="btn btn-primary btn-sm save-basket" name="basket_new_save_clear" data-basket-clear="1">SAVE AND CLEAR</button>
                            <button type="submit" class="btn btn-danger btn-sm save-basket" name="basket_new_save" data-basket-clear="0">SAVE</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

        return new JsonResponse($response);
    }

    #[Route('/clinics/checkout', name: 'checkout_shipping')]
    public function shippingCheckoutAction(Request $request): Response
    {
        $user = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());
        $clinic_id = $this->getUser()->getClinic()->getId();
        $basket_id = $request->request->get('basket_id') ?? $request->get('basket_id');
        $basket = $this->em->getRepository(Baskets::class)->find($basket_id);
        $baskets = $this->em->getRepository(Baskets::class)->findBy(['clinic' => $clinic_id]);
        $clinic_totals = $this->em->getRepository(Baskets::class)->getClinicTotalItems($clinic_id);
        $saved_items = $this->em->getRepository(ClinicProducts::class)->findBy([
            'clinic' => $this->getUser()->getClinic()->getId()
        ]);

        $total_clinic = number_format($clinic_totals[0]['total'] ?? 0,2);
        $count_clinic = $clinic_totals[0]['item_count'] ?? 0;

        $response = '
        <div class="row">
            <!-- Left Column -->
            <div class="col-12 col-md-2 col-100" id="basket_left_col">
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
                </div>';

        foreach($baskets as $individual_basket){

            $count = $individual_basket->getBasketItems()->count();
            $bg_primary = '';
            $active = '';

            if($count > 0){

                $bg_primary = 'bg-primary';
            }

            if($individual_basket->getId() == $basket_id){

                $active = 'active-basket';
            }

            $response .= '
            <div class="row">
                <div class="col-12 border-bottom '. $active .'">
                    <a href="#" data-basket-id="'. $individual_basket->getId() .'" class=" pt-3 pb-3 d-block basket-link">
                        <span class="d-inline-block align-baseline">'. $individual_basket->getName() .'</span>
                        <span class="float-end basket-item-count-empty '. $bg_primary .'">
                            '. $count .'
                        </span>
                    </a>
                </div>
            </div>';
        }

        $response .= '
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
        <div class="col-12 col-md-10 col-100 border-left" id="basket_items">
            <!-- Basket Name -->
            <div class="row">
                <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3">
                    <h4 class="text-white">'. $basket->getName() .' Basket</h4>
                    <span class="text-white">
                        Manage All Your Shopping Carts In One Place
                    </span>
                </div>
            </div>
            <!-- Basket Actions Upper Row -->
            <div class="row">
                <div class="col-12 d-flex justify-content-evenly border-bottom pt-3 pb-3">
                    <a href="#">
                        <i class="fa-solid fa-arrow-rotate-right me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Refresh Basket</span>
                    </a>
                    <a href="#" id="print_basket">
                        <i class="fa-solid fa-print me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Print</span>
                    </a>
                    <a href="#" id="saved_baskets_link" data-basket-id="'. $basket_id .'">
                        <i class="fa-solid fa-basket-shopping me-0 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Saved Baskets</span>
                    </a>
                    <a href="#" id="return_to_search">
                        <i class="fa-solid fa-magnifying-glass me-0 me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Back To Search</span>
                    </a>
                </div>
            </div>';

        if(count($basket->getBasketItems()) > 0) {

            $response .= '
            <!-- Basket Actions Lower Row -->
            <div class="row">
                <div class="col-12 d-flex justify-content-evenly border-bottom pt-3 pb-3">
                    <a href="#" class="save-all-items" data-basket-id="' . $basket_id . '">
                        <i class="fa-regular fa-bookmark me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Save All For Later</span>
                    </a>
                    <a href="#" class="clear-basket" data-basket-id="' . $basket_id . '">
                        <i class="fa-solid fa-trash-can me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Clear Basket</span>
                    </a>
                    <a href="#" class="refresh-basket" data-basket-id="'. $basket_id .'">
                        <i class="fa-solid fa-arrow-rotate-right me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Refresh Basket</span>
                    </a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modal_save_basket">
                        <i class="fa-solid fa-basket-shopping me-md-2"></i><span class=" d-none d-md-inline-block pe-3">Save Basket</span>
                    </a>
                </div>
            </div>';
        }

        $basket_summary = false;
        $col = '12';

        if(count($basket->getBasketItems()) > 0) {

            $basket_summary = true;
            $col = '9 border-right';
        }

        $response .= '
                <!-- Basket Items -->
                <div class="row col-container d-flex border-0 m-0">
                    <div class="col-12 col-md-'. $col .' col-cell ps-0">';

        $i = -1;

        if(count($basket->getBasketItems()) > 0) {

            foreach ($basket->getBasketItems() as $item) {

                $i++;
                //dd($basket->getBasketItems()[2]->getProduct()->getId());
                $product = $basket->getBasketItems()[$i]->getProduct();
                $shipping_policy = $item->getDistributor()->getShippingPolicy();

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
                                    <div class="col-4 text-center text-sm-end">$' . number_format($item->getUnitPrice(),2) . '</div>
                                    <div class="col-4">
                                        <input 
                                            type="text" 
                                            list="qty_list_' . $product->getId() . '" 
                                            data-basket-item-id="' . $item->getId() . '" 
                                            name="qty" 
                                            class="form-control basket-qty" 
                                            value="' . $item->getQty() . '" 
                                            ng-value="' . $item->getQty() . '"
                                        >
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
                                    <div class="col-4 text-center text-sm-start fw-bold">$' . number_format($item->getTotal(),2) . '</div>
                                </div>
                            </div>
                        </div>
                        <!-- Item Actions -->
                        <div class="row">
                            <div class="col-12">
                                <!-- In Stock -->
                                '. $stock_badge .'
                                <!-- Shipping Policy -->
                                <span class="badge bg-dark-grey" class="btn btn-secondary" data-bs-trigger="hover"
                                      data-bs-container="body" data-bs-toggle="popover" data-bs-placement="top" data-bs-html="true"
                                      data-bs-content="'. $shipping_policy .'">Shipping Policy</span>
                                <!-- Remove Item -->
                                <span class="badge bg-danger float-end">
                                    <a href="#" class="remove-item text-white" data-item-id="' . $item->getId() . '">Remove</a>
                                </span>
                                <!-- Save Item -->
                                <span class="badge badge-light float-end me-2">
                                    <a href="#" class="link-secondary save-item" data-basket-id="'. $basket_id .'" data-product-id="'. $product->getId() .'" data-distributor-id="'. $item->getDistributor()->getId() .'" data-item-id="' . $item->getId() . '">Save Item For later</a>
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
                    </div>';

        if($basket_summary) {

            $response .= '
            <!-- Basket Summary -->
            <div class="col-12 col-md-3 pt-3 pb-3 pe-0 col-cell">
                <div class="row">
                    <div class="col-12 text-truncate">
                        <span class="info">Subtotal:</span>
                        <h5 class="d-inline-block text-primary float-end">$' . number_format($basket->getTotal(),2) . '</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 info">
                        Shipping: <span class="float-end fw-bold">$6.99</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 pt-4 text-center">
                        <a href="" class="btn btn-primary w-100" id="btn_checkout">
                            PROCEED TO CHECKOUT <i class="fa-solid fa-circle-right ps-2"></i>
                        </a>
                    </div>
                </div>
            </div>';
        }

        $response .= '
                </div>';

        // Saved Items
        if(count($saved_items) > 0){

            $plural = '';

            if(count($saved_items) > 1){

                $plural = 's';
            }

            $response .= '
                    <div class="row" style="background: #f4f8fe">
                        <div class="col-12 border-bottom border-top pt-3 pb-3">
                            <a href="" id="saved_items_link">Items Saved for Later ('. count($saved_items) .' Item'. $plural .')</a>
                        </div>
                    </div>
                    <div class="row" id="saved_items_container">
                        <div class="col-12 border-bottom border-top pt-3 pb-3 position-relative">
                            <a href="" class="btn btn-primary btn-sm restore-all" id="restore_all" data-basket-id="'. $basket_id .'">Move All To Basket</a>
                ';

            foreach($saved_items as $item){

                $product = $item->getProduct();

                $response .= '
                    <div class="row">
                        <!-- Thumbnail -->
                        <div class="col-12 col-sm-2 text-center pt-3 pb-3">
                            <img class="img-fluid basket-img" src="/images/products/' . $product->getImage() . '">
                        </div>
                        <div class="col-12 col-sm-10 pt-3 pb-3">
                            <div class="row">
                                <!-- Product Name -->
                                <div class="col-12 col-sm-7">
                                    <h6 class="fw-bold text-center text-sm-start text-primary lh-base mb-0">
                                        ' . $product->getName() . ': ' . $product->getDosage() . ' ' . $product->getUnit() . ', Each
                                    </h6>
                                    Saved on '. $item->getModified()->format('M jS Y') .' by '. $item->getSavedBy() .'<br>
                                    <span class="badge badge-light me-2 mt-2">
                                        <a href="#" class="link-secondary restore-item" data-basket-id="'. $basket_id .'" data-product-id="'. $product->getId() .'" data-distributor-id="'. $item->getDistributor()->getId() .'" data-item-id="'. $item->getId() .'">
                                            Move To Basket
                                        </a>
                                    </span>
                                    <span class="badge bg-danger mt-2">
                                        <a href="#" class="text-white remove-saved-item" data-basket-id="" data-item-id="'. $item->getId() .'">
                                            Remove
                                        </a>
                                    </span>
                                </div>
                            </div>
                       
                        </div>
                    </div>';
            }
        }

        $response .= '     
                    </div>
                </div>   
            </div>
        </div>
        <!-- Modal Save Basket -->
        <div class="modal fade" id="modal_save_basket" tabindex="-1" aria-labelledby="save_basket_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form name="form_save_basket" id="form_save_basket" method="post">
                        <input type="hidden" name="basket_id" value="'. $basket_id .'">
                        <div class="modal-header basket-modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <h6>Clear current basket?</h6>
                                    After you save this basket for later, would you like to clear this basket?
                                </div>
                                <div class="col-12 mb-0">
                                    <input type="text" class="form-control" name="basket_name" id="basket_name" placeholder="Basket Name">
                                </div>
                                <div class="hidden_msg" id="error_basket_name">
                                    Please enter name for the basket
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="submit" class="btn btn-primary btn-sm save-basket" name="basket_new_save_clear" data-basket-clear="1">SAVE AND CLEAR</button>
                            <button type="submit" class="btn btn-danger btn-sm save-basket" name="basket_new_save" data-basket-clear="0">SAVE</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

        return new JsonResponse($response);
    }

    private function setSavedBy()
    {
        return $this->getUser()->getFirstName() .' '. $this->getUser()->getLastName();
    }
}
