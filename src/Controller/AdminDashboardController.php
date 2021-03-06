<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Categories;
use App\Entity\Clinics;
use App\Entity\ClinicUserPermissions;
use App\Entity\ClinicUsers;
use App\Entity\Manufacturers;
use App\Entity\ProductManufacturers;
use App\Entity\Products;
use App\Entity\ProductsSpecies;
use App\Entity\Species;
use App\Entity\SubCategories;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Entity\CommunicationMethods;
use App\Services\PaginationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    private $em;
    private $page_manager;
    private $passwordHasher;
    private $mailer;
    const ITEMS_PER_PAGE = 7;

    public function __construct(
        EntityManagerInterface $em, PaginationManager $page_manager,
        UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer
    )
    {
        $this->em = $em;
        $this->page_manager = $page_manager;
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
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

    #[Route('/admin/product/crud', name: 'product_crud')]
    public function productCrudAction(Request $request): Response
    {
        $productId = $request->get('product_id') ?? $request->request->get('delete');
        $product = $this->em->getRepository(Products::class)->find($productId);

        if($request->request->get('delete') != null){

            $productManufacturers = $this->em->getRepository(ProductManufacturers::class)->findBy([
                'products' => $productId,
            ]);
            $productSpecies = $this->em->getRepository(ProductsSpecies::class)->findBy([
                'products' => $productId,
            ]);

            foreach($productManufacturers as $productManufacturer){

                $this->em->remove($productManufacturer);
            }

            foreach($productSpecies as $productSpecie) {

                $this->em->remove($productSpecie);
            }

            $this->em->flush();

            $this->em->remove($product);
            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> Product Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        if($product == null){

            $product = new Products();
        }

        $response = [];

        if(!empty($request->request)) {

            $data = $request->request;
            $productId = $request->get('product_id');
            $manufacturers = $this->em->getRepository(ProductManufacturers::class)->findBy([
                'products' => $productId,
            ]);
            $productSpecies = $this->em->getRepository(ProductsSpecies::class)->findBy([
                'products' => $productId,
            ]);
            $category = $this->em->getRepository(Categories::class)->find($data->get('category'));
            $subCategory = $this->em->getRepository(SubCategories::class)->find($data->get('subCategory'));

            // Clear many to many tables
            foreach($manufacturers as $manufacturer){
                dump($manufacturer->getId());
                $this->em->remove($manufacturer);
            }

            foreach($productSpecies as $species){

                $this->em->remove($species);
            }

            $this->em->flush();

            $product->setIsPublished($data->get('is_published'));
            $product->setExpiryDateRequired($expDate = $data->get('expiry_date') ?? 0);

            foreach($data->get('manufacturers') as $manufacturer){

                $productManufacturer = new ProductManufacturers();
                $manu = $this->em->getRepository(Manufacturers::class)->find($manufacturer);

                $productManufacturer->setProducts($product);
                $productManufacturer->setManufacturers($manu);

                $this->em->persist($productManufacturer);
            }

            $product->setName($data->get('name'));

            foreach($data->get('species') as $species){

                $productSpecies = new ProductsSpecies();
                $specie = $this->em->getRepository(Species::class)->find($species);

                $productSpecies->setProducts($product);
                $productSpecies->setSpecies($specie);

                $this->em->persist($productSpecies);
            }

            $product->setCategory($category);
            $product->setSubCategory($subCategory);
            $product->setSku($data->get('serial_no'));
            $product->setActiveIngredient($data->get('active_ingredient'));
            $product->setDosage($data->get('dosage'));
            $product->setSize($data->get('size'));
            $product->setUnit($data->get('unit'));
            $product->setUnitPrice($data->get('price'));
            $product->setStockCount($data->get('stock'));
            $product->setPackType($data->get('package_type'));
            $product->setForm($data->get('form'));

            // Image
            if(!empty($_FILES['image']['name'])) {

                $fileName = $_FILES['image'];
                $extension = pathinfo($fileName['name'], PATHINFO_EXTENSION);
                $newFileName = uniqid('fluis_'. $product->getId() .'_', true) . '.' . $extension;
                $filePath = __DIR__ . '/../../public/images/products/';

                if($x = move_uploaded_file($fileName['tmp_name'], $filePath . $newFileName)){

                    $product->setImage($newFileName);
                }
            }

            $product->setDescription($data->get('details'));

            $this->em->persist($product);
            $this->em->flush();

            $response['flash'] = '<b><i class="fas fa-check-circle"></i> Product updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
            $response['product'] = $product->getName();
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/category/crud', name: 'category_crud')]
    public function categoryCrudAction(Request $request): Response
    {
        $categoryId = $request->get('category_id') ?? $request->request->get('delete');
        $category = $this->em->getRepository(Categories::class)->find($categoryId);

        if($request->request->get('delete') != null){

            $subCategories = $this->em->getRepository(SubCategories::class)->findBy([
                'category' => $categoryId,
            ]);

            foreach($subCategories as $subCategory){

                $subCategory->setCategory(0);
                $this->em->persist($subCategory);
            }

            $this->em->flush();

            $this->em->remove($category);

            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> Category Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        if($category == null){

            $category = new Categories();
        }

        $response['flash'] = '';

        if(!empty($request->request)) {

            $subCategories = $this->em->getRepository(SubCategories::class)->findBy([
                'category'=>  $categoryId,
            ]);

            if(count($subCategories) > 0){

                foreach ($subCategories as $subCategory) {

                    $subCategory->setCategory(null);

                    $this->em->persist($subCategory);
                }

                $this->em->flush();

                foreach ($request->request->get('sub_categories') as $subCategoryId) {

                    $subCategory = $this->em->getRepository(SubCategories::class)->find($subCategoryId);

                    $subCategory->setCategory($category);

                    $this->em->persist($subCategory);
                }
            }

            $category->setCategory($request->request->get('category'));

            $this->em->persist($category);
            $this->em->flush();

            $response['category'] = $request->request->get('category');
            $response['flash'] = '<b><i class="fas fa-check-circle"></i> Category updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/clinic/crud', name: 'clinic_crud')]
    public function clinicCrudAction(Request $request): Response
    {
        $data = $request->request;
        $clinicId = $request->get('clinic_id') ?? $data->get('delete');
        $clinic = $this->em->getRepository(Clinics::class)->find($clinicId);
        $response['clinicUsers'] = $this->em->getRepository(ClinicUsers::class)->findBy([
            'clinic' => $clinicId,
        ]);

        if($data->get('delete') != null){

            $addresses = $this->em->getRepository(Addresses::class)->findBy([
                'clinic' => $clinicId
            ]);

            $flash = '<b><i class="fas fa-check-circle"></i> Category Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        $response['flash'] = '';

        if(!empty($data)) {

            // Clinic Details
            $clinic->setClinicName($data->get('clinic_name'));
            $clinic->setEmail($data->get('email'));
            $clinic->setTelephone($data->get('telephone'));

            $this->em->persist($clinic);

            // Clinic Users
            if(count($data->get('user_id')) > 0){

                for($i = 0; $i < count($data->get('user_id')); $i++){

                    $userId = $data->get('user_id')[$i];
                    $firstName = $data->get('user_first_name')[$i];
                    $lastName = $data->get('user_last_name')[$i];
                    $userEmail = $data->get('user_email')[$i];
                    $userTelephone = $data->get('user_telephone')[$i];

                    $clinicUsers = $this->em->getRepository(ClinicUsers::class)->find($userId);

                    $clinicUsers->setFirstName($firstName);
                    $clinicUsers->setLastName($lastName);
                    $clinicUsers->setEmail($userEmail);
                    $clinicUsers->setTelephone($userTelephone);

                    $this->em->persist($clinicUsers);

                    // User Permissions
                    $userPermissions = $this->em->getRepository(ClinicUserPermissions::class)->findBy([
                        'user' => $userId
                    ]);

                    // Remove currently saved
                    foreach($userPermissions as $userPermission){

                        $this->em->remove($userPermission);
                    }

                    // Save new permissions
                    foreach($data->get('user_permissions') as $permissionId){

                        $pieces = explode('_', $permissionId);

                        if($pieces[1] == $clinicUsers->getId()) {

                            $userPermission = new ClinicUserPermissions();
                            $permission = $this->em->getRepository(UserPermissions::class)->find($permissionId);

                            $userPermission->setPermission($permission);
                            $userPermission->setClinic($clinic);
                            $userPermission->setUser($clinicUsers);

                            $this->em->persist($userPermission);
                        }
                    }
                }
            }

            $this->em->flush();

            $response['flash'] = '<b><i class="fas fa-check-circle"></i> Cliinic Successfully Updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
            $response['clinicName'] = $data->get('clinic_name');
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/communication-method/crud', name: 'communication_method_crud')]
    public function communicationMethodCrudAction(Request $request): Response
    {
        $data = $request->request;
        $communicationMethodId = $data->get('communicationMethodId');
        $communicationMethod = $this->em->getRepository(CommunicationMethods::class)->find($communicationMethodId);

        $response = [];

        if(!empty($data)) {

            // Clinic Details
            $communicationMethod->setMethod($data->get('communication_method'));

            $this->em->persist($communicationMethod);
            $this->em->flush();

            $response['flash'] = '<b><i class="fas fa-check-circle"></i> Communication Method Successfully Updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
            $response['method'] = $data->get('communication_method');
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/manufacturer/crud', name: 'manufacturer_crud')]
    public function manufacturerCrudAction(Request $request): Response
    {
        $data = $request->request;
        $manufacturerId = $data->get('manufacturerId') ?? $request->request->get('delete');
        $manufacturer = $this->em->getRepository(Manufacturers::class)->find($manufacturerId);

        $response = [];

        if($request->request->get('delete') != null){

            $productManufacturers = $this->em->getRepository(ProductManufacturers::class)->findBy([
                'manufacturers' => $manufacturerId,
            ]);

            foreach ($productManufacturers as $productManufacturer) {

                $this->em->remove($productManufacturer);
            }

            $this->em->flush();

            $this->em->remove($manufacturer);
            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> Manufacturer Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        if(!empty($data)) {

            if($manufacturer == null){

                $manufacturer = new Manufacturers();
            }

            // Clinic Details
            $manufacturer->setName($data->get('manufacturer_name'));

            $this->em->persist($manufacturer);
            $this->em->flush();

            $response['flash'] = '<b><i class="fas fa-check-circle"></i> Manufacturer Successfully Updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
            $response['manufacturer'] = $data->get('manufacturer_name');
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/species/crud', name: 'species_crud')]
    public function speciesCrudAction(Request $request): Response
    {
        $data = $request->request;
        $speciesId = $data->get('speciesId') ?? $request->request->get('delete');
        $species = $this->em->getRepository(Species::class)->find($speciesId);

        $response = [];

        if($request->request->get('delete') != null){

            $productSpecies = $this->em->getRepository(ProductsSpecies::class)->findBy([
                'species' => $speciesId,
            ]);

            foreach ($productSpecies as $productSpecie) {

                $this->em->remove($productSpecie);
            }

            $this->em->flush();

            $this->em->remove($species);
            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> Species Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        if(!empty($data)) {

            if($species == null){

                $species = new Species();
            }

            $species->setName($data->get('species_name'));

            $this->em->persist($species);
            $this->em->flush();

            $response['flash'] = '<b><i class="fas fa-check-circle"></i> Species Successfully Updated.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
            $response['species'] = $species->getName();
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/user/crud', name: 'user_crud')]
    public function userCrudAction(Request $request): Response
    {
        $data = $request->request;
        $userId = $data->get('userId') ?? $request->request->get('delete');
        $user = $this->em->getRepository(User::class)->find($userId);

        $response = [];

        if($request->request->get('delete') != null){

            $this->em->remove($user);
            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> User Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        if(!empty($data)) {

            $action = 'Updated';
            $newUser = false;

            if($user == null){

                $validUser = $this->em->getRepository(User::class)->findBy([
                    'email' => $data->get('email'),
                ]);

                if(count($validUser) > 0){

                    $response['flash'] = '<b><i class="fas fa-check-circle"></i> User account not created!.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

                    return new JsonResponse($response);
                }

                $newUser = true;
                $action = 'Created';
                $user = new User();
            }

            $user->setFirstName($data->get('first_name'));
            $user->setLastName($data->get('last_name'));
            $user->setEmail($data->get('email'));

            // User Roles
            if(count($data->get('roles')) > 0){

                $roles = [];

                foreach($data->get('roles') as $role){

                    $roles[] = $role;
                }

                $user->setRoles($roles);
            }

            $this->em->persist($user);
            $this->em->flush();

            if($newUser || $data->get('resetPassword') == 'true'){

                $plainPwd = $this->setUserPassword($user->getId())['plainPassword'];
                $hashedPwd = $this->setUserPassword($user->getId())['hashedPassword'];

                $user->setPassword($hashedPwd);

                $this->em->persist($user);
                $this->em->flush();

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2">Hi '. $user->getFirstName() .',</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the Fluid Backend.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://'. $_SERVER['HTTP_HOST'] .'/admin">https://'. $_SERVER['HTTP_HOST'] .'/admin</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $user->getEmail() .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plainPwd .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from($this->getParameter('app.email_from'))
                    ->addTo($data->get('email'))
                    ->subject('Fluid Login Credentials')
                    ->html($body);

                $this->mailer->send($email);
            }

            $response['flash'] = '<b><i class="fas fa-check-circle"></i> User account successfully '. $action .'!.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
            $response['user'] = $user->getFirstName() .' '. $user->getLastName();
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/user-permissions/crud', name: 'user_permission_crud')]
    public function userPermissionCrudAction(Request $request): Response
    {
        $data = $request->request;
        $userPermissionId = $data->get('permissionId') ?? $request->request->get('delete');
        $userPermission = $this->em->getRepository(UserPermissions::class)->find($userPermissionId);
        $action = 'Updated';

        $response = [];

        if($request->request->get('delete') != null){

            $this->em->remove($userPermission);
            $this->em->flush();

            $flash = '<b><i class="fas fa-check-circle"></i> User Permission Successfully Deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

            return new JsonResponse($flash);
        }

        if(!empty($data)) {

            if($userPermission == null){

                $userPermission = new UserPermissions();
                $action = 'Created';
            }

            $isClinic = $data->get('isClinic') ?? 0;
            $isDistributor = $data->get('isDistributor') ?? 0;

            $userPermission->setIsClinic($isClinic);
            $userPermission->setIsDistributor($isDistributor);
            $userPermission->setPermission($data->get('permission'));
            $userPermission->setInfo($data->get('description'));

            $this->em->persist($userPermission);
            $this->em->flush();
        }

        $response['flash'] = '<b><i class="fas fa-check-circle"></i> User Successfully '. $action .'.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        $response['permission'] = $data->get('permission');

        return new JsonResponse($response);
    }

    #[Route('/admin/product/manufacturer/save', name: 'products_manufacturer_save')]
    public function productsSaveManufacturer(Request $request): Response
    {
        $data = $request->request;
        $manufacturer_id = $data->get('manufacturer_id');
        $manufacturer_name = $data->get('manufacturer');
        $manufacturer = $this->em->getRepository(Manufacturers::class)->find($manufacturer_id);
        $response = false;

        if($manufacturer != null && $manufacturer_id > 0){

            $manufacturer->setName($manufacturer_name);

            $this->em->persist($manufacturer);
            $this->em->flush();

            $response = true;

        } elseif($manufacturer_id == 0){

            $manufacturer = new Manufacturers();

            $manufacturer->setName($manufacturer_name);

            $this->em->persist($manufacturer);
            $this->em->flush();

            $manufacturers = $this->em->getRepository(Manufacturers::class)->findAll();

            $response = $this->getDropdownList(
                $manufacturers, 'manufacturer', ProductsSpecies::class, 'getName',
                'products', $request->get('product_id'), 'getSpecies'
            );
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/product/species/save', name: 'products_species_save')]
    public function productsSaveSpecies(Request $request): Response
    {
        $data = $request->request;
        $species_id = $data->get('species_id');
        $species_name = $data->get('species');
        $species = $this->em->getRepository(Species::class)->find($species_id);
        $response = false;

        if($species != null && $species_id > 0){

            $species->setName($species_name);

            $this->em->persist($species);
            $this->em->flush();

            $response = true;

        } elseif($species_id == 0){

            $species = new Species();

            $species->setName($species_name);

            $this->em->persist($species);
            $this->em->flush();

            $species = $this->em->getRepository(Species::class)->findAll();

            $response = $this->getDropdownList(
                $species, 'species', ProductsSpecies::class, 'getName',
                'products', $request->get('product_id'), 'getSpecies'
            );
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/categories/sub-categories/save', name: 'categories_sub_categories_save')]
    public function categorySaveSubCategory(Request $request): Response
    {
        $data = $request->request;
        $sub_category_id = $data->get('sub_category_id');
        $sub_category = $data->get('sub_category');
        $subCategory = $this->em->getRepository(SubCategories::class)->find($sub_category_id);
        $response = false;

        if($subCategory != null && $sub_category_id > 0){

            $subCategory->setSubCategory($sub_category);

            $this->em->persist($subCategory);
            $this->em->flush();

            $response = true;

        } elseif($sub_category_id == 0){

            $subCategory = new SubCategories();

            $subCategory->setSubCategory($sub_category);

            $this->em->persist($subCategory);
            $this->em->flush();

            $subCategory = $this->em->getRepository(SubCategories::class)->findAll();

            $response = $this->getDropdownList(
                $subCategory, 'sub_category', SubCategories::class, 'getSubCategory',
                'category', $request->get('category_id'), 'getCategory'
            );
        }

        return new JsonResponse($response);
    }

    #[Route('/admin/product/is-published', name: 'product_is_published')]
    public function productIsPublished(Request $request): Response
    {
        $isPublished = $request->request->get('is_published') ?? 0;
        $productId = $request->request->get('product_id');

        $product = $this->em->getRepository(Products::class)->find($productId);

        if($product != null){

            $product->setIsPublished($isPublished);

            $this->em->persist($product);
            $this->em->flush();
        }

        return new JsonResponse($isPublished);
    }

    #[Route('/admin/user-permission/is-clinic', name: 'permission_is_clinic')]
    public function permissionIsClinic(Request $request): Response
    {
        $isClinic = $request->request->get('is_clinic') ?? 0;
        $permissionId = $request->request->get('permission_id');

        $userPermission = $this->em->getRepository(UserPermissions::class)->find($permissionId);

        if($userPermission != null){

            $userPermission->setIsClinic($isClinic);

            $this->em->persist($userPermission);
            $this->em->flush();
        }

        return new JsonResponse($isClinic);
    }

    #[Route('/admin/user-permission/is-distributor', name: 'permission_is_distributor')]
    public function permissionIsDistributor(Request $request): Response
    {
        $isDistributor = $request->request->get('is_distributor') ?? 0;
        $permissionId = $request->request->get('permission_id');

        $userPermission = $this->em->getRepository(UserPermissions::class)->find($permissionId);

        if($userPermission != null){

            $userPermission->setIsDistributor($isDistributor);

            $this->em->persist($userPermission);
            $this->em->flush();
        }

        return new JsonResponse($isDistributor);
    }

    #[Route('/admin/product/{product_id}', name: 'products', requirements: ['product_id' => '\d+'])]
    public function productsCrud(Request $request, $product_id = 0): Response
    {
        $product = $this->em->getRepository(Products::class)->find($request->get('product_id'));
        $manufacturers = $this->em->getRepository(Manufacturers::class)->findAll();
        $species = $this->em->getRepository(Species::class)->findAll();
        $categories = $this->em->getRepository(Categories::class)->findAll();
        $subCategories = $this->em->getRepository(SubCategories::class)->findAll();
        $productManufacturers = $this->em->getRepository(ProductManufacturers::class)->findBy([
            'products' => $request->get('product_id'),
        ]);
        $productSpecies = $this->em->getRepository(ProductsSpecies::class)->findBy([
            'products' => $request->get('product_id'),
        ]);

        if($product == null){

            $product = new Products();
        }

        // Manufacturers dropdown
        $manufacturersList = '';

        if($manufacturers != null){

            $manufacturersList = $this->getDropdownList(
                $manufacturers, 'manufacturer', ProductManufacturers::class, 'getName',
                'products', $request->get('product_id'), 'getProducts'
            );
            $array = '';
            $arr = '[';

            foreach($productManufacturers as $productManufacturer){

                $array .= $productManufacturer->getManufacturers()->getId().',';
            }

            $arr .= trim($array,',') . ']';
        }

        // Species dropdown
        $speciesList = '';

        if($species != null){

            $speciesList = $this->getDropdownList(
                $species, 'species', ProductManufacturers::class, 'getName',
                'products', $request->get('product_id'), 'getProducts'
            );
            $array = '';
            $arr_species = '[';

            foreach($productSpecies as $productSpecie){

                $array .= $productSpecie->getSpecies()->getId().',';
            }

            $arr_species .= trim($array,',') . ']';
        }


        return $this->render('Admin/products.html.twig',[
            'product' => $product,
            'manufacturers' => $manufacturers,
            'species' => $species,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'product_id' => $request->get('product_id'),
            'manufacturersList' => $manufacturersList,
            'productManufacturers' => $productManufacturers,
            'speciesList' => $speciesList,
            'productSpecies' => $productSpecies,
            'arr' => $arr,
            'arr_species' => $arr_species,
        ]);
    }

    #[Route('/admin/user-permission/{permission_id}', name: 'user_permissions', requirements: ['permission_id' => '\d+'])]
    public function userPermissionsCrud(Request $request, $permission_id = 0): Response
    {
        $userPermission = $this->em->getRepository(UserPermissions::class)->find($request->get('permission_id'));

        if($userPermission == null){

            $userPermission = new UserPermissions();
        }

        return $this->render('Admin/user_permissions.html.twig',[
            'permission' => $userPermission,
        ]);
    }

    #[Route('/admin/categories/{page_id}', name: 'categories_list')]
    public function categoriesList(Request $request): Response
    {
        $categories = $this->em->getRepository(Categories::class)->adminFindAll();
        $results = $this->page_manager->paginate($categories[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/categories/');

        return $this->render('Admin/categories_list.html.twig',[
            'categories' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/category/{category_id}', name: 'categories', requirements: ['category_id' => '\d+'])]
    public function categoriesCrud(Request $request, $category_id = 0): Response
    {
        $category = $this->em->getRepository(Categories::class)->find($request->get('category_id'));
        $subCategory = $this->em->getRepository(SubCategories::class)->findAll();
        $category_id = $request->get('category_id') ?? 0;
        $selectedSubCategories = $this->em->getRepository(SubCategories::class)->findBy([
            'category' => $category_id
        ]);

        if($category == null){

            $category = new Categories();
        }

        // Sub Category dropdown
        $subCategoriesList = '';
        $arr = [];

        $subCategoriesList = $this->getDropdownList(
            $subCategory, 'sub_category', SubCategories::class, 'getSubCategory',
            'category', $request->get('category_id'), 'getCategory'
        );

        if(!empty($selectedSubCategories)){

            $array = '';
            $arr = '[';

            foreach($selectedSubCategories as $selectedSubCategory){

                $array .= $selectedSubCategory->getId().',';
            }

            $arr .= trim($array,',') . ']';
        }

        return $this->render('Admin/categories.html.twig',[
            'category' => $category,
            'subCategories' => $subCategory,
            'category_id' => $category_id,
            'selectedSubCategories' => $selectedSubCategories,
            'subCategoriesList' => $subCategoriesList,
            'arr' => $arr,
        ]);
    }

    #[Route('/admin/clinics/{page_id}', name: 'clinics_list')]
    public function clinicsList(Request $request): Response
    {
        $clinics = $this->em->getRepository(Clinics::class)->adminFindAll();
        $results = $this->page_manager->paginate($clinics[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/clinics/');

        return $this->render('Admin/clinics_list.html.twig',[
            'clinics' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/communication-methods/{page_id}', name: 'communication_methods_list')]
    public function communicationMethodsList(Request $request): Response
    {
        $communicationMethods = $this->em->getRepository(CommunicationMethods::class)->adminFindAll();
        $results = $this->page_manager->paginate($communicationMethods[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/communication-methods/');

        return $this->render('Admin/communication_methods_list.html.twig',[
            'communicationMethods' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/manufacturers/{page_id}', name: 'manufacturers_list')]
    public function manufacturersList(Request $request): Response
    {
        $manufacturers = $this->em->getRepository(Manufacturers::class)->adminFindAll();
        $results = $this->page_manager->paginate($manufacturers[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/manufacturers/');

        return $this->render('Admin/manufacturers_list.html.twig',[
            'manufacturers' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/users/{page_id}', name: 'users_list')]
    public function usersList(Request $request): Response
    {
        $users = $this->em->getRepository(User::class)->adminFindAll();
        $results = $this->page_manager->paginate($users[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/users/');

        return $this->render('Admin/users_list.html.twig',[
            'users' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/species/{page_id}', name: 'species_list')]
    public function speciesList(Request $request): Response
    {
        $species = $this->em->getRepository(Species::class)->adminFindAll();
        $results = $this->page_manager->paginate($species[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/species/');

        return $this->render('Admin/species_list.html.twig',[
            'species' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/user-permissions/{page_id}', name: 'user_permissions_list')]
    public function userPermissionsList(Request $request): Response
    {
        $userPermissions = $this->em->getRepository(UserPermissions::class)->adminFindAll();
        $results = $this->page_manager->paginate($userPermissions[0], $request, self::ITEMS_PER_PAGE);
        $pagination = $this->getPagination($request->get('page_id'), $results, '/admin/user-permissions/');

        return $this->render('Admin/user_permissions_list.html.twig',[
            'permissions' => $results,
            'pagination' => $pagination
        ]);
    }

    #[Route('/admin/clinic/{clinic_id}', name: 'clinics', requirements: ['clinic_id' => '\d+'])]
    public function clinicsCrud(Request $request, $clinic_id = 0): Response
    {
        $clinicId = $request->get('clinic_id') ?? 0;
        $clinic = $this->em->getRepository(Clinics::class)->find($clinicId);
        $clinicUsers = $this->em->getRepository(ClinicUsers::class)->findBy([
            'clinic' => $clinicId
        ]);
        $userPermissions = $this->em->getRepository(UserPermissions::class)->findBy([
            'isClinic' => 1
        ]);

        if($clinic == null){

            $clinic = new Clinics();
        }

        return $this->render('Admin/clinics.html.twig',[
            'clinic' => $clinic,
            'clinicUsers' => $clinicUsers,
            'userPermissions' => $userPermissions
        ]);
    }

    #[Route('/admin/communication-method/{communicationMethodId}', name: 'communication_methods', requirements: ['communicationMethodId' => '\d+'])]
    public function communicationMethodsCrud(Request $request, $communicationMethodId = 0): Response
    {
        $communicationMethodId = $request->get('communicationMethodId') ?? 0;
        $communicationMethod = $this->em->getRepository(CommunicationMethods::class)->find($communicationMethodId);

        if($communicationMethod == null){

            $clinic = new CommunicationMethods();
        }

        return $this->render('Admin/communication_methods.html.twig',[
            'communicationMethod' => $communicationMethod,
        ]);
    }

    #[Route('/admin/manufacturer/{manufacturerId}', name: 'manufacturers', requirements: ['manufacturerId' => '\d+'])]
    public function manufacturersCrud(Request $request, $manufacturerId = 0): Response
    {
        $manufacturerId = $request->get('manufacturerId') ?? 0;
        $manufacturer = $this->em->getRepository(Manufacturers::class)->find($manufacturerId);

        if($manufacturer == null){

            $manufacturer = new Manufacturers();
        }

        return $this->render('Admin/manufacturers.html.twig',[
            'manufacturer' => $manufacturer,
        ]);
    }

    #[Route('/admin/specie/{speciesId}', name: 'species', requirements: ['speciesId' => '\d+'])]
    public function speciesCrud(Request $request, $speciesId = 0): Response
    {
        $speciesId = $request->get('speciesId') ?? 0;
        $species = $this->em->getRepository(Species::class)->find($speciesId);

        if($species == null){

            $species = new Species();
        }

        return $this->render('Admin/species.html.twig',[
            'species' => $species,
        ]);
    }

    #[Route('/admin/user/{userId}', name: 'users', requirements: ['userId' => '\d+'])]
    public function usersCrud(Request $request, $userId = 0): Response
    {
        $usersId = $request->get('userId') ?? 0;
        $users = $this->em->getRepository(User::class)->find($usersId);

        if($users == null){

            $users = new User();
        }

        $rolesList = $this->getRolesDropdownList($users->getId());

        $array = '';
        $arr = '[';

        foreach($users->getRoles() as $role){

            $array .= '"'. $role .'",';
        }

        $arr .= trim($array,',') . ']';

        return $this->render('Admin/users.html.twig',[
            'users' => $users,
            'rolesList' => $rolesList,
            'arr' => $arr,
        ]);
    }

    private function getDropdownList($repository, $label, $entity, $name, $foreign_key, $entity_id, $method)
    {
        $list = '
        <div class="px-3 row">
            <div class="bg-dropdown px-0 col-12">';

        // Loop through all dropdown options
        foreach($repository as $repo){

            // Get related records
            $query = $this->em->getRepository($entity)->findBy([
                $foreign_key => $entity_id,
            ]);

            $select = $label . '-select';

            if($entity_id > 0) {

                foreach ($query as $qry) {

                    // Remove class identifier for adding
                    if ($qry->$method()->getId() == $repo->getId()) {

                        $select = '';

                        break;
                    }
                }

            // New product
            } else {

                $select = $label . '-select';
            }


            $list .= '
            <div class="row">
            <div 
                class="col-12 edit-'. $label .' d-table"
                data-'. $label .'-id="'. $repo->getId() .'"
                
            >
                <div 
                    class="row '. $label .'-row d-table-row" data-'. $label .'-id="'. $repo->getId() .'">
                    <div 
                        class="col-10 py-2 d-table-cell align-middle '. $select .'"
                        data-'. $label .'-id="'. $repo->getId() .'"
                        data-'. $label .'="'. $repo->$name() .'"
                        id="'. $label .'_row_id_'. $repo->getId() .'"
                    >
                            <span id="'. $label .'_string_'. $repo->getId() .'">
                                '. $repo->$name() .'
                            </span>
                            <input 
                                type="text" 
                                class="form-control form-control-sm '. $label .'-form-ctrl"
                                value="'. $repo->$name() .'"
                                data-'. $label .'-field-'. $repo->getId() .'
                                id="'. $label .'_edit_field_'. $repo->getId() .'"
                                style="display: none"
                            >
                            <div class="hidden_msg" id="error_'. $label .'_'. $repo->getId() .'">
                                Required Field
                            </div>
                        </div>
                        <div class="col-2 py-2 d-table-cell align-middle">
                            <a 
                                href="" 
                                class="float-end '. $label .'-edit-icon me-3" 
                                id="'. $label .'_edit_'. $repo->getId() .'"
                                data-'. $label .'-edit-id="'. $repo->getId() .'"
                                style="display: none"
                            >
                               <i class="fa-solid fa-pen-to-square"></i>
                           </a>
                           <a 
                                href="" 
                                class="float-end '. $label .'-remove-icon me-3" 
                                id="'. $label .'_remove_'. $repo->getId() .'"
                                data-'. $label .'-id="'. $repo->getId() .'"
                                style="display: none"
                            >
                               <i class="fa-solid fa-circle-minus"></i>
                           </a>
                           <a 
                                href="" 
                                class="float-end '. $label .'-cancel-icon me-3" 
                                id="'. $label .'_cancel_'. $repo->getId() .'"
                                data-'. $label .'-cancel-id="'. $repo->getId() .'"
                                style="display: none"
                            >
                               <i class="fa-solid fa-xmark"></i>
                           </a>
                           <a 
                                href="" 
                                class="float-end '. $label .'-save-icon me-3" 
                                id="'. $label .'_save_'. $repo->getId() .'"
                                data-'. $label .'-id="'. $repo->getId() .'"
                                style="display: none"
                            >
                               <i class="fa-solid fa-floppy-disk"></i>
                           </a>
                        </div>
                    </div>
            </div>
            </div>';
        }

        $list .= '
                <div class="col-12 d-table">
                    <div class="row d-table-row" id="'. $label .'_add">
                        <div class="col-10 py-2 d-table-cell align-middle text-info">
                            <span id="'. $label .'_create_string" role="button">
                                <i class="fa-regular fa-square-plus me-2"></i>
                                Add '. ucfirst($label) .'
                            </span>
                            <input 
                                type="text" 
                                class="form-control form-control-sm"
                                id="'. $label .'_create_field"
                                style="display: none"
                            >
                            <div class="hidden_msg" id="error_'. $label .'_create">
                                Required Field
                            </div>
                        </div>
                        <div 
                            class="col-2 py-2 d-table-cell align-middle text-info"
                            role="button"
                        >
                            <a 
                                href="" 
                                class="float-end '. $label .'-create-cancel-icon me-3" 
                                style="display: none"
                            >
                               <i class="fa-solid fa-xmark"></i>
                           </a>
                           <a 
                                href="" 
                                class="float-end '. $label .'-create-save-icon me-3" 
                                style="display: none"
                            >
                               <i class="fa-solid fa-floppy-disk"></i>
                           </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return $list;
    }

    private function getRolesDropdownList($userId)
    {
        $userId = $userId ?? 0;
        $repository = [
            "ROLE_USER",
            "ROLE_ADMIN",
            "ROLE_CATEGORY",
            "ROLE_CLINIC",
            "ROLE_COMMUNICATION_METHOD",
            "ROLE_SPECIE",
            "ROLE_SUB_CATEGORY",
        ];

        $list = '
        <div class="px-3 row">
            <div class="bg-dropdown px-0 col-12">';

        // Loop through all dropdown options
        foreach($repository as $repo){

            // Get related records
            $query = $this->em->getRepository(User::class)->find($userId);

            $select = 'role-select';

            if($query != null) {

                foreach ($query->getRoles() as $role) {

                    // Remove class identifier for adding
                    if ($role == $repo) {

                        $select = '';
                        break;
                    }
                }
            }


            $list .= '
            <div class="row">
                <div 
                    class="col-12 edit-role d-table"
                    data-role-id="'. $repo .'"
                    
                >
                    <div 
                        class="row role-row d-table-row" data-role-id="'. $repo .'">
                        <div 
                            class="col-10 py-2 d-table-cell align-middle '. $select .'"
                            data-role-id="'. $repo .'"
                            data-role="'. $repo .'"
                            id="role_row_id_'. $repo .'"
                        >
                                <span id="role_string_'. $repo .'">
                                    '. $repo .'
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control form-control-sm role-form-ctrl"
                                    value="'. $repo .'"
                                    data-role-field-'. $repo .'
                                    id="role_edit_field_'. $repo .'"
                                    style="display: none"
                                >
                                <div class="hidden_msg" id="error_role_'. $repo .'">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-2 py-2 d-table-cell align-middle">
                               <a 
                                    href="" 
                                    class="float-end role-remove-icon me-3" 
                                    id="role_remove_'. $repo .'"
                                    data-role-id="'. $repo .'"
                                    style="display: none"
                                >
                                   <i class="fa-solid fa-circle-minus"></i>
                               </a>
                               <a 
                                    href="" 
                                    class="float-end role-cancel-icon me-3" 
                                    id="role_cancel_'. $repo .'"
                                    data-role-cancel-id="'. $repo .'"
                                    style="display: none"
                                >
                                   <i class="fa-solid fa-xmark"></i>
                               </a>
                               <a 
                                    href="" 
                                    class="float-end role-save-icon me-3" 
                                    id="role_save_'. $repo .'"
                                    data-role-id="'. $repo .'"
                                    style="display: none"
                                >
                                   <i class="fa-solid fa-floppy-disk"></i>
                               </a>
                            </div>
                        </div>
                </div>
            </div>';
        }

        $list .= '
            </div>
        </div>';

        return $list;
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

    private function setUserPassword($userId)
    {
        // ... e.g. get the user data from a registration form
        $user = $this->em->getRepository(User::class)->find($userId);

        $plaintextPassword = $this->generatePassword();

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );

        return [
            'plainPassword' => $plaintextPassword,
            'hashedPassword' => $hashedPassword
        ];
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