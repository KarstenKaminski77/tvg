<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class ProductsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Products::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->renderContentMaximized()
            ->setPageTitle('index', 'Products')
            ->setPageTitle('new', 'New Product')
            ->setPageTitle('detail', fn (Products $products) => (string) $products)
            ->setPageTitle('edit', fn (Products $products) => sprintf('Editing <b>%s</b>', $products->getName()))
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Products');;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            BooleanField::new('isPublished', 'Published')->setColumns(6),
            BooleanField::new('expiryDateRequired', 'Expiry Date')->setColumns(6),
            AssociationField::new('productManufacturer', 'Manufacturer')
                ->setColumns(6)->setRequired(true)->onlyOnForms(),
            TextField::new('name', 'Name')->setColumns(6),
            AssociationField::new('productsSpecies', 'Species')
                ->setColumns(6)->setRequired(true)->onlyOnForms(),
            AssociationField::new('category', 'Category')
                ->setRequired(true)->setColumns(6),
            AssociationField::new('subCategory', 'Sub Category')
                ->setRequired(true)->setColumns(6),
            TextField::new('sku', 'Serial Number    ')->setColumns(6),
            TextField::new('activeIngredient', 'Active Ingredient')
                ->onlyOnForms()->setColumns(6),
            TextField::new('dosage', 'Dosage')
                ->onlyOnForms()->setColumns(6)->setRequired(false),
            TextField::new('size', 'Size')
                ->onlyOnForms()->setColumns(6)->setRequired(true),
            ChoiceField::new('unit', 'Unit')
                ->setChoices([
                    'tablet' => 'tablet',
                    'mg' => 'mg',
                    'ml' => 'ml'
                ])->setColumns(6)->onlyOnForms(),
            MoneyField::new('unitPrice', 'Price')->setCurrency('USD')->setColumns(6),
//            TextField::new('containerType', 'Container Type')
//                ->onlyOnForms()->setColumns(6),

            IntegerField::new('stockCount', 'Stock')->setColumns(6),
            ChoiceField::new('packType', 'Package Type')->setChoices([
                'Bottle' => 'Bottle',
                'Gel' => 'Del',
            ])->onlyOnForms()->setColumns(6),
            TextField::new('form', 'Form')->onlyOnForms()->setColumns(6),
            ImageField::new('image', 'Image')
                ->setBasePath('/images/products')
                ->setUploadDir('/public/images/products')
                ->setUploadedFileNamePattern('[contenthash]')->setColumns(6),
            TextareaField::new('description', 'Description')
                ->setFormType(CKEditorType::class)->onlyOnForms()->setColumns(12),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
        ];
    }
}
