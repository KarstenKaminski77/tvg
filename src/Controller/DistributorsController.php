<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\AvailabilityTracker;
use App\Entity\ChatMessages;
use App\Entity\Clinics;
use App\Entity\Countries;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\DistributorUsers;
use App\Entity\Notifications;
use App\Entity\OrderItems;
use App\Entity\Orders;
use App\Entity\Products;
use App\Form\AddressesFormType;
use App\Form\DistributorFormType;
use App\Form\DistributorProductsFormType;
use App\Form\DistributorUsersFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class DistributorsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/distributors', name: 'distributors')]
    public function index(): Response
    {
        return $this->render('distributors/index.html.twig', [
            'controller_name' => 'DistributorsController',
        ]);
    }

    #[Route('/distributors/register', name: 'distributor_reg')]
    public function distributorReg(Request $request): Response
    {
        $form = $this->createRegisterForm();

        return $this->render('frontend/distributors/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function createRegisterForm()
    {
        $distributors = new Distributors();

        return $this->createForm(DistributorFormType::class, $distributors);
    }

    #[Route('/distributor/inventory', name: 'distributor_inventory')]
    public function createDistributorInventoryForm()
    {
        $distributorProducts = new DistributorProducts();

        return $this->createForm(DistributorProductsFormType::class, $distributorProducts);
    }

    #[Route('/distributor/addresses', name: 'distributor_addresses')]
    public function createDistributorAddressesForm()
    {
        $addresses = new Addresses();

        return $this->createForm(AddressesFormType::class, $addresses);
    }

    #[Route('/distributor/register/create', name: 'distributor_create')]
    public function distributorCreateAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request;
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $data->get('email')]);

        if($distributor == null) {

            $distributors = new Distributors();

            $plain_text_pwd = $this->generatePassword();

            if (!empty($plain_text_pwd)) {

                $distributors->setDistributorName($data->get('distributor_name'));
                $distributors->setEmail($data->get('email'));
                $distributors->setTelephone($data->get('telephone'));

                $this->em->persist($distributors);
                $this->em->flush();

                // Create user
                $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $data->get('email')]);
                $distributor_users = new DistributorUsers();

                $hashed_pwd = $passwordHasher->hashPassword($distributor_users, $plain_text_pwd);

                $distributor_users->setDistributor($distributor);
                $distributor_users->setFirstName($data->get('first_name'));
                $distributor_users->setLastName($data->get('last_name'));
                $distributor_users->setPosition($data->get('position'));
                $distributor_users->setEmail($data->get('email'));
                $distributor_users->setTelephone($data->get('telephone'));
                $distributor_users->setRoles(['ROLE_DISTRIBUTOR']);
                $distributor_users->setPassword($hashed_pwd);
                $distributor_users->setIsPrimary(1);

                $this->em->persist($distributor_users);
                $this->em->flush();

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $data->get('first_name') .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/distributors/login">https://'. $_SERVER['HTTP_HOST'] .'/distributor/login</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $data->get('email') .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plain_text_pwd .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($data->get('email'))
                    ->subject('Fluid Login Credentials')
                    ->html($body);

                $mailer->send($email);
            }

            $response = 'Your Fluid account was successfully created, an email with your login credentials has been sent to your inbox.';

        } else {

            $response = false;
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/dashboard', name: 'distributor_dashboard')]
    #[Route('/distributors/order/{order_id}', name: 'distributor_order')]
    public function distributorDashboardAction(Request $request): Response
    {
        if($this->get('security.token_storage')->getToken() == null){

            $this->addFlash('danger', 'Your session expired due to inactivity, please login.');

            return $this->redirectToRoute('distributor_login');
        }

        $distributor = $this->getUser()->getDistributor();
        $user = $this->getUser();
        $form = $this->createRegisterForm();
        $inventoryForm = $this->createDistributorInventoryForm();
        $addressForm = $this->createDistributorAddressesForm();
        $user_form = $this->createDistributorUserForm()->createView();
        $clinic_id = '';
        if($request->get('order_id') != null) {

            $order = $this->em->getRepository(Orders::class)->find($request->get('order_id'));
            $clinic_id = $order->getClinic()->getId();
        }
        $order_list = false;
        $order_detail = false;

        if(substr($request->getPathInfo(),0,20) == '/distributors/orders'){

            $order_list = true;
        }

        if(substr($request->getPathInfo(),0,20) == '/distributors/order/'){

            $order_detail = true;
        }

        return $this->render('frontend/distributors/dashboard.html.twig',[
            'distributor' => $distributor,
            'user' => $user,
            'form' => $form->createView(),
            'inventory_form' => $inventoryForm->createView(),
            'address_form' => $addressForm->createView(),
            'user_form' => $user_form,
            'order_list' => $order_list,
            'order_detail' => $order_detail,
            'clinic_id' => $clinic_id,
        ]);
    }

    #[Route('/distributors/get-user', name: 'distributor_get_user')]
    public function distributorGetUserAction(Request $request): Response
    {
        $user = $this->em->getRepository(DistributorUsers::class)->find($request->request->get('id'));

        $response = [

            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'position' => $user->getPosition(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/users', name: 'distributor_users')]
    public function distributorUsersAction(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $data = $request->request->get('distributor_users_form');
        $distributor = $this->get('security.token_storage')->getToken()->getUser()->getDistributor();
        $user = $this->em->getRepository(DistributorUsers::class)->findBy(['email' => $data['email']]);
        $user_id = $data['user_id'];

        if(count($user) > 0){

            $response = [
                'response' => false
            ];

            return new JsonResponse($response);
        }

        if($user_id == 0){

            $distributor_user = new DistributorUsers();

            $plain_text_pwd = $this->generatePassword();

            if (!empty($plain_text_pwd)) {

                $hashed_pwd = $passwordHasher->hashPassword($distributor_user, $plain_text_pwd);

                $distributor_user->setRoles(['ROLE_USER']);
                $distributor_user->setPassword($hashed_pwd);

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $data['firstName'] .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/distributors/login">https://'. $_SERVER['HTTP_HOST'] .'/distributors/login</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $data['email'] .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plain_text_pwd .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($data['email'])
                    ->subject('Fluid Login Credentials')
                    ->html($body);

                $mailer->send($email);
            }

            $message = '<b><i class="fas fa-check-circle"></i> User details successfully created.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $distributor_user = $this->em->getRepository(DistributorUsers::class)->find($user_id);

            $distributor_user->setIsPrimary(0);

            $message = '<b><i class="fas fa-check-circle"></i> User successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $distributor_user->setDistributor($distributor);
        $distributor_user->setFirstName($data['firstName']);
        $distributor_user->setLastName($data['lastName']);
        $distributor_user->setEmail($data['email']);
        $distributor_user->setTelephone($data['telephone']);
        $distributor_user->setPosition($data['position']);
        $distributor_user->setIsPrimary(0);

        $this->em->persist($distributor_user);
        $this->em->flush();

        $response = [

            'response' => true,
            'message' => $message
        ];

        return new JsonResponse($response);
    }

    public function createDistributorUserForm()
    {
        $distributor_users = new DistributorUsers();

        return $this->createForm(DistributorUsersFormType::class, $distributor_users);
    }

    #[Route('/distributor/update/personal-information', name: 'distributor_update_personal_information')]
    public function distributorUpdatePersonalInformationAction(Request $request): Response
    {
        $data = $request->request;
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);

        if($distributor != null) {

            $distributor->setFirstName($data->get('first_name'));
            $distributor->setLastName($data->get('last_name'));
            $distributor->setTelephone($data->get('telephone'));
            $distributor->setPosition($data->get('position'));

            $this->em->persist($distributor);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Personal details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/users-refresh', name: 'distributor_refresh_users')]
    public function distributorRefreshUsersAction(Request $request): Response
    {
        $distributor_id = $this->get('security.token_storage')->getToken()->getUser()->getDistributor()->getId();
        $users = $this->em->getRepository(Distributors::class)->getDistributorUsers($distributor_id);

        $html = '';

        foreach($users[0]->getDistributorUsers() as $user){

            $html .= '<div class="list-width">
                       <div class="row t-row">
                           <div class="col-md-2 t-cell" id="string_user_first_name_'. $user->getId() .'">
                               '. $user->getFirstName() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_last_name_'. $user->getId() .'">
                               '. $user->getLastName() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_email_'. $user->getId() .'">
                               '. $user->getEmail() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_telephone_'. $user->getId() .'">
                               '. $user->getEmail() .'
                           </div>
                           <div class="col-md-2 t-cell" id="string_user_position_'. $user->getId() .'">
                               '. $user->getPosition() .'
                           </div>
                           <div class="col-md-2 t-cell">
                               <a href="" class="float-end" data-bs-toggle="modal" data-bs-target="#modal_user" id="user_update_{{ users.id }}">
                                   <i class="fa-solid fa-pen-to-square edit-icon"></i>
                               </a>
                               <a href="" class="delete-icon float-end" data-bs-toggle="modal"
                                  data-value="{{ users.id }}" data-bs-target="#modal_user_delete" id="user_delete_{{ users.id }}">
                                   <i class="fa-solid fa-trash-can"></i>
                               </a>
                           </div>
                       </div>
                   </div>';
        }

        return new JsonResponse($html);
    }

    #[Route('/distributors/update/company-information', name: 'distributor_update_company_information')]
    public function distributorUpdateCompanyInformationAction(Request $request): Response
    {
        $data = $request->request->get('distributor_form');

        $distributor = $this->getUser()->getDistributor();
        $country_id = $data['addressCountry'];
        $logo = '';

        $country = $this->em->getRepository(Countries::class)->find($country_id);

        if($distributor != null) {

            $distributor->setDistributorName($data['distributorName']);
            $distributor->setTelephone($data['telephone']);
            $distributor->setEmail($data['email']);
            $distributor->setWebsite($data['website']);
            $distributor->setAddressCountry($country);
            $distributor->setAddressStreet($data['addressStreet']);
            $distributor->setAddressCity($data['addressCity']);
            $distributor->setAddressPostalCode($data['addressPostalCode']);
            $distributor->setAddressState($data['addressState']);

            if(!empty($_FILES['distributor_form']['name']['logo'])) {

                $extension = pathinfo($_FILES['distributor_form']['name']['logo'], PATHINFO_EXTENSION);
                $file = $distributor->getId() . '-' . uniqid() . '.' . $extension;
                $target_file = __DIR__ . '/../../public/images/logos/' . $file;

                if (move_uploaded_file($_FILES['distributor_form']['tmp_name']['logo'], $target_file)) {

                    $distributor->setLogo($file);
                    $logo = $file;
                }
            }

            $this->em->persist($distributor);
            $this->em->flush();

            $message = '<b><i class="fa-solid fa-circle-check"></i></i></b> Company details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $message = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        $response = [
            'message' => $message,
            'logo' => $logo,
        ];

        return new JsonResponse($response);
    }

    #[Route('/distributors/update/about_us', name: 'distributor_update_about_us')]
    public function distributorUpdateAboutUsAction(Request $request): Response
    {
        $data = $request->request;
        $distributor = $this->getUser()->getDistributor();

        if($distributor != null) {

            $about = $data->get('about_us');

            if(!empty($about)) {

                $distributor->setAbout($about);

                $this->em->persist($distributor);
                $this->em->flush();
            }

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> About us successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> An error occurred.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/update/operating_hours', name: 'distributor_update_operating_hours')]
    public function distributorUpdateOperatingHoursAction(Request $request): Response
    {
        $data = $request->request;
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);

        if($distributor != null) {

            if(!empty($data->get('operating_hours'))) {

                $distributor->setOperatingHours($data->get('operating_hours'));
            }

            $this->em->persist($distributor);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Operating hours successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/update/refund_policy', name: 'distributor_update_refund_policy')]
    public function distributorUpdateRefundPolicyAction(Request $request): Response
    {
        $data = $request->request;
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);

        if($distributor != null) {

            if(!empty($data->get('refund_policy'))) {

                $distributor->setRefundPolicy($data->get('refund_policy'));
            }

            $this->em->persist($distributor);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Refund policy successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/update/sales_tax_policy', name: 'distributor_update_sales_tax_policy')]
    public function distributorUpdateSalesTaxPolicyAction(Request $request): Response
    {
        $data = $request->request;
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);

        if($distributor != null) {

            if(!empty($data->get('sales_tax_policy'))) {

                $distributor->setSalesTaxPolicy($data->get('sales_tax_policy'));
            }

            $this->em->persist($distributor);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Sales tax policy successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/inventory-search', name: 'distributor_inventory_search')]
    public function distributorInventorySearchAction(Request $request): Response
    {
        $products = $this->em->getRepository(Products::class)->findBySearch($request->get('keyword'));
        $select = '<ul id="product_list">';

        foreach($products as $product){

            $id = $product->getId();
            $name = $product->getName();
            $dosage = '';
            $size = '';

            if(!empty($product->getDosage())) {

                $unit = '';

                if(!empty($product->getUnit())) {

                    $unit = $product->getUnit();
                }

                $dosage = ' | '. $product->getDosage() . $unit;
            }

            if(!empty($product->getSize())) {

                $size = ' | '. $product->getSize();
            }

            $select .= "<li onClick=\"selectProduct('$id', '$name');\">$name$dosage$size</li>";
        }

        $select .= '</ul>';

        return new Response($select);
    }

    #[Route('/distributors/inventory-get', name: 'distributor_inventory_get')]
    public function distributorGetInventoryAction(Request $request,TokenStorageInterface $tokenStorage): Response
    {
        $products = $this->em->getRepository(Products::class)->find($request->get('product_id'));

        if($products != null){

            $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();

            $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);
            $response = [];

            $distributor_product = $this->em->getRepository(Distributors::class)
                ->getDistributorProduct($distributor->getId(), $request->get('product_id'));

            if($distributor_product != null){

                $response['distributor_id'] = $distributor->getId();
                $response['sku'] = $distributor_product[0]['distributorProducts'][0]['sku'];
                $response['distributor_no'] = $distributor_product[0]['distributorProducts'][0]['distributorNo'];
                $response['unit_price'] = $distributor_product[0]['distributorProducts'][0]['unitPrice'];
                $response['stock_count'] = $distributor_product[0]['distributorProducts'][0]['stockCount'];
                $response['expiry_date'] = $distributor_product[0]['distributorProducts'][0]['expiryDate']->format('Y-m-d');
                $response['tax_exempt'] = $distributor_product[0]['distributorProducts'][0]['taxExempt'];
                $response['product'] = $distributor_product[0]['distributorProducts'][0]['product'];

            } else {

                $product = $this->em->getRepository(Products::class)->find($request->get('product_id'));

                $response['distributor_id'] = $distributor->getId();
                $response['sku'] = '';
                $response['distributor_no'] = '';
                $response['unit_price'] = '';
                $response['stock_count'] = '';
                $response['expiry_date'] = '';
                $response['tax_exempt'] = 0;
                $response['product'] = [
                    'dosage' => $product->getDosage(),
                    'size' => $product->getSize(),
                    'packType' => $product->getPackType(),
                    'unit' => $product->getUnit(),
                    'activeIngredient' => $product->getActiveIngredient(),
                ];
            }

        } else {

            $response['message'] = 'Inventory item not found';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/inventory-update', name: 'distributor_inventory_update')]
    public function distributorUpdateInventoryAction(Request $request, MailerInterface $mailer): Response
    {
        $data = $request->request->get('distributor_products_form');

        $product = $this->em->getRepository(Products::class)->find($data['product']);
        $distributor = $this->em->getRepository(Distributors::class)->find($data['distributor']);
        $distributor_products = $this->em->getRepository(DistributorProducts::class)->findOneBy(
            [
                'product' => $data['product'],
                'distributor' => $data['distributor']
            ]
        );
        $tracking = false;

        if($distributor_products == null){

            $distributor_products = new DistributorProducts();

        } else {

            if($distributor_products->getStockCount() == 0){

                $tracking = true;
            }
        }

        if(!empty($data['product']) && !empty($data['distributor'])){

            $distributor_products->setDistributor($distributor);
            $distributor_products->setProduct($product);
            $distributor_products->setSku($data['sku']);
            $distributor_products->setDistributorNo($data['distributorNo']);
            $distributor_products->setUnitPrice($data['unitPrice']);
            $distributor_products->setStockCount($data['stockCount']);

            if($product->getExpiryDateRequired() == 1) {

                $distributor_products->setExpiryDate(\DateTime::createFromFormat('Y-m-d', $data['expiryDate']));
            }

            $distributor_products->setTaxExempt($data['taxExempt']);

            $tax_exempt = 0;

            if(!empty($data['taxExempt'])){

                $tax_exempt = $data['taxExempt'];
            }

            $distributor_products->setTaxExempt($tax_exempt);

            $this->em->persist($distributor_products);
            $this->em->flush();

            // Update parent stock level
            $stock_count = $this->em->getRepository(DistributorProducts::class)->getProductStockCount($product->getId());

            $product->setStockCount($stock_count[0][1]);

            // Get the lowest price
            $lowest_price = $this->em->getRepository(DistributorProducts::class)->getLowestPrice($product->getId());

            $product->setUnitPrice($lowest_price[0]['unitPrice']);

            $this->em->persist($product);
            $this->em->flush();

            // Availability Tracker
            $availability_tracker = '';

            if($tracking){

                $availability_tracker = $this->em->getRepository(AvailabilityTracker::class)->findBy([
                    'product' => $product->getId(),
                    'distributor' => $data['distributor'],
                    'isSent' => 0,
                ]);

                foreach($availability_tracker as $tracker){

                    $method_id = $tracker->getCommunication()->getCommunicationMethod()->getId();
                    $send_to = $tracker->getCommunication()->getSendTo();
                    $product = $tracker->getProduct();

                    // In app notifications
                    if($method_id == 1){

                        $notifications = new Notifications();

                        $notifications->setClinic($tracker->getClinic());
                        $notifications->setIsRead(0);
                        $notifications->setIsActive(1);
                        $notifications->setAvailabilityTracker($tracker);

                        $this->em->persist($notifications);
                        $this->em->flush();

                        // Get the newly created notification
                        $notification = '
                        <table class="w-100">
                            <tr>
                                <td><span class="badge bg-success me-3">New Stock</span></td>
                                <td>'. $product->getName() .' '. $product->getDosage() . $product->getUnit() .'</td>
                                <td>
                                    <a href="#" class="delete-notification" data-notification-id="'. $notifications->getId() .'">
                                        <i class="fa-solid fa-xmark text-black-25 ms-3 float-end"></i>
                                    </a>
                                </td>
                            </tr>
                        </table>';

                        $notifications = $this->em->getRepository(Notifications::class)->find($notifications->getId());

                        $notifications->setNotification($notification);

                        $this->em->persist($notifications);
                        $this->em->flush();

                    // Email notifications
                    } elseif($method_id == 2){

                        $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                        $body .= '<tr><td colspan="2">'. $product->getName() .' '. $product->getDosage() . $product->getUnit() .' is back in stock</td></tr>';
                        $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                        $body .= '<tr>';
                        $body .= '    <td><b>Distributor: </b></td>';
                        $body .= '    <td>'. $tracker->getDistributor()->getDistributorName() .'</td>';
                        $body .= '</tr>';
                        $body .= '<tr>';
                        $body .= '    <td><b>Stock Level: </b></td>';
                        $body .= '    <td>'. $tracker->getProduct()->getDistributorProducts()[0]->getStockCount() .'</td>';
                        $body .= '</tr>';
                        $body .= '</table>';

                        $email = (new Email())
                        ->from($this->getParameter('app.email_from'))
                        ->addTo($send_to)
                        ->subject('Fluid Stock Level Update')
                        ->html($body);

                        $mailer->send($email);

                    // Text notifications
                    } elseif($method_id == 3){

                    }

                    $availabilityTracker = $this->em->getRepository(AvailabilityTracker::class)->find($tracker->getId());
                    $availabilityTracker->setIsSent(1);

                    $this->em->persist($availabilityTracker);
                    $this->em->flush();
                }
            }

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> '. $product->getName() .' successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = 'An error occurred';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/user/delete', name: 'distributor_user_delete')]
    public function distributorDeleteUser(Request $request): Response
    {
        $user_id = $request->request->get('id');
        $user = $this->em->getRepository(DistributorUsers::class)->find($user_id);

        $this->em->remove($user);
        $this->em->flush();

        $response = '<b><i class="fas fa-check-circle"></i> User successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    private function generatePassword()
    {
        $sets = [];
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $sets[] = '23456789';
        $sets[] = '!@$%*?';

        $all = '';
        $password = '';

        foreach ($sets as $set) {

            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);

        for ($i = 0; $i < 16 - count($sets); $i++) {

            $password .= $all[array_rand($all)];
        }

        $this->plain_password = str_shuffle($password);

        return $this->plain_password;
    }
}
