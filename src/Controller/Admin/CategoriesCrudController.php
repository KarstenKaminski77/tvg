<?php

namespace App\Controller\Admin;

use App\Entity\Categories;
use App\Entity\SubCategories;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoriesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Categories::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            ->setPageTitle('index', 'Categories')
            ->setPageTitle('new', 'New Category')
            ->setPageTitle('detail', fn (Categories $categories) => (string) $categories)
            ->setPageTitle('edit', fn (Categories $categories) => sprintf('Editing <b>%s</b>', $categories->getCategory()))
            ->setEntityLabelInSingular('Category')
            ->setEntityLabelInPlural('Categories');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            TextField::new('category', 'Category')->setColumns(12),
            AssociationField::new('subCategories', 'Sub Categories')->setColumns(12),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
        ];
    }

}
