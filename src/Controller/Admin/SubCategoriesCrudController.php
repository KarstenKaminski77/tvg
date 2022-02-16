<?php

namespace App\Controller\Admin;

use App\Entity\SubCategories;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SubCategoriesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubCategories::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            ->setPageTitle('index', 'Sub Categories')
            ->setPageTitle('new', 'New Sub Category')
            ->setPageTitle('detail', fn (SubCategories $subCategories) => (string) $subCategories)
            ->setPageTitle('edit', fn (SubCategories $subCategories) => sprintf('Editing <b>%s</b>', $subCategories->getCategory()->getCategory() .' > '. $subCategories->getSubCategory()))
            ->setEntityLabelInSingular('Sub Category')
            ->setEntityLabelInPlural('Sub Categories');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            AssociationField::new('category', 'Category')
                ->setRequired(true)->setColumns(6),
            TextField::new('subCategory', 'Sub Category')
                ->setRequired(true)->setColumns(6),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
        ];
    }
}
