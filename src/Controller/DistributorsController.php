<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\Products;
use App\Form\AddressesFormType;
use App\Form\DistributorFormType;
use App\Form\DistributorProductsFormType;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use function PHPUnit\Framework\throwException;

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

    #[Route('/distributor/register', name: 'distributor_reg')]
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

            $distributor = new Distributors();

            $plain_text_pwd = $this->generatePassword();
            $hashed_pwd = $passwordHasher->hashPassword($distributor, $plain_text_pwd);

            if (!empty($plain_text_pwd)) {

                $distributor->setDistributorName($data->get('distributor_name'));
                $distributor->setFirstName($data->get('first_name'));
                $distributor->setLastName($data->get('last_name'));
                $distributor->setEmail($data->get('email'));
                $distributor->setTelephone($data->get('telephone'));
                $distributor->setRoles(['ROLE_USER']);
                $distributor->setPassword($hashed_pwd);

                $this->em->persist($distributor);
                $this->em->flush();

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $distributor->getFirstName() .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the TVG Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/distributor/login">https://'. $_SERVER['HTTP_HOST'] .'/distributor/login</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $distributor->getUsername() .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plain_text_pwd .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($data->get('email'))
                    ->subject('TVG Login Credentials')
                    ->html($body);

                $mailer->send($email);
            }

            $response = true;

        } else {

            $response = false;
        }

        return new JsonResponse($response);
    }

    #[Route('/distributors/dashboard', name: 'distributor_dashboard')]
    public function distributorDashboardAction(Request $request): Response
    {
        if($this->get('security.token_storage')->getToken() == null){

            $this->addFlash('danger', 'Your session expired due to inactivity, please login.');

            return $this->redirectToRoute('distributor_login');
        }

        $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $user_name]);
        $form = $this->createRegisterForm();
        $inventoryForm = $this->createDistributorInventoryForm();
        $addressForm = $this->createDistributorAddressesForm();

        return $this->render('frontend/distributors/dashboard.html.twig',[
            'distributor' => $distributor,
            'form' => $form->createView(),
            'inventory_form' => $inventoryForm->createView(),
            'address_form' => $addressForm->createView(),
        ]);
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

    #[Route('/distributor/update/company-information', name: 'distributor_update_company_information')]
    public function distributorUpdateCompanyInformationAction(Request $request): Response
    {
        $data = $request->request->get('distributor_form');
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);

        if($distributor != null) {

            if(!empty($data['distributorName'])) {

                $distributor->setDistributorName($data['distributorName']);
            }

            if(!empty($data['website'])) {

                $distributor->setWebsite($data['website']);
            }

            if(!empty($_FILES['distributor_form']['name']['logo'])) {

                $extension = pathinfo($_FILES['distributor_form']['name']['logo'], PATHINFO_EXTENSION);
                $file = $distributor->getId() . '-' . uniqid() . '.' . $extension;
                $target_file = __DIR__ . '/../../public/images/logos/' . $file;

                if (move_uploaded_file($_FILES['distributor_form']['tmp_name']['logo'], $target_file)) {

                    $distributor->setLogo($file);
                }
            }

            $this->em->persist($distributor);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Company details successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributor/update/about_us', name: 'distributor_update_about_us')]
    public function distributorUpdateAboutUsAction(Request $request): Response
    {
        $data = $request->request;
        $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);

        if($distributor != null) {

            if(!empty($data->get('about_us'))) {

                $distributor->setAbout($data->get('about_us'));
            }

            $this->em->persist($distributor);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> About us successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = '<b><i class="fas fa-check-circle"></i> Personal details successfully updated.';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributor/update/operating_hours', name: 'distributor_update_operating_hours')]
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

    #[Route('/distributor/update/refund_policy', name: 'distributor_update_refund_policy')]
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

    #[Route('/distributor/update/sales_tax_policy', name: 'distributor_update_sales_tax_policy')]
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

    #[Route('/distributor/inventory-search', name: 'distributor_inventory_search')]
    public function distributorInventorySearchAction(Request $request): Response
    {
        $products = $this->em->getRepository(Products::class)->findBySearch($request->get('keyword'));
        $select = '<ul id="product_list">';

        foreach($products as $product){

            $id = $product->getId();
            $name = $product->getName();

            $select .= "<li onClick=\"selectProduct('$id', '$name');\">$name</li>";
        }

        $select .= '</ul>';

        return new Response($select);
    }

    #[Route('/distributor/inventory-get', name: 'distributor_inventory_get')]
    public function distributorGetInventoryAction(Request $request): Response
    {
        $products = $this->em->getRepository(Products::class)->find($request->get('product_id'));

        if($products != null){

            $username = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
            $distributor = $this->em->getRepository(Distributors::class)->findOneBy(['email' => $username]);
            $response = [];

            $response['distributor_id'] = $distributor->getId();
            $response['sku'] = '';
            $response['distributor_no'] = '';
            $response['unit_price'] = '';
            $response['stock_count'] = 0;
            $response['expiry_date'] = '';
            $response['tax_exempt'] = 0;

            $distributor_product = $this->em->getRepository(DistributorProducts::class)->findOneBy(['distributor' => $distributor->getId()]);

            if($distributor_product != null){

                $response['sku'] = $distributor_product->getSku();
                $response['distributor_no'] = $distributor_product->getDistributorNo();
                $response['unit_price'] = $distributor_product->getUnitPrice();
                $response['stock_count'] = $distributor_product->getStockCount();
                $response['expiry_date'] = $distributor_product->getExpiryDate()->format('Y-m-d');
                $response['tax_exempt'] = $distributor_product->getTaxExempt();
            }

        } else {

            $response['message'] = 'Inventory item not found';
        }

        return new JsonResponse($response);
    }

    #[Route('/distributor/inventory-update', name: 'distributor_inventory_update')]
    public function distributorUpdateInventoryAction(Request $request): Response
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

        if($distributor_products == null){

            $distributor_products = new DistributorProducts();

        }

        if(!empty($data['product']) && !empty($data['distributor'])){

            $distributor_products->setDistributor($distributor);
            $distributor_products->setProduct($product);
            $distributor_products->setSku($data['sku']);
            $distributor_products->setDistributorNo($data['distributorNo']);
            $distributor_products->setUnitPrice($data['unitPrice']);
            $distributor_products->setStockCount($data['stockCount']);
            $distributor_products->setExpiryDate(\DateTime::createFromFormat('Y-m-d', $data['expiryDate']));

            $tax_exempt = 0;

            if(!empty($data['taxExempt'])){

                $tax_exempt = $data['taxExempt'];
            }

            $distributor_products->setTaxExempt($tax_exempt);

            $this->em->persist($distributor_products);
            $this->em->flush();

            $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> '. $product->getName() .' successfully updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            $response = 'An error occurred';
        }

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
