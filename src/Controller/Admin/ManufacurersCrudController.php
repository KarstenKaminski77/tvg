<?php

namespace App\Controller\Admin;

use App\Entity\Manufacturers;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ManufacurersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Manufacturers::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            ->setPageTitle('index', 'Manufacturers')
            ->setPageTitle('new', 'New Manufacturers')
            ->setPageTitle('detail', fn (Manufacturers $manufacturers) => (string) $manufacturers)
            ->setPageTitle('edit', fn (Manufacturers $manufacturers) => sprintf('Editing <b>%s</b>', $manufacturers->getName()))
            ->setEntityLabelInSingular('Manufacturers')
            ->setEntityLabelInPlural('Manufacturers');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            TextField::new('name', 'Name')->setRequired(true)->setColumns(12),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
        ];
    }
}
