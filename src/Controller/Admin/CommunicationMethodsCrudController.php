<?php

namespace App\Controller\Admin;

use App\Entity\CommunicationMethods;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CommunicationMethodsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CommunicationMethods::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            ->setPageTitle('index', 'Communication Methods')
            ->setPageTitle('new', 'New Communication Method')
            ->setPageTitle('detail', fn (CommunicationMethods $communicationMethods) => (string) $communicationMethods)
            ->setPageTitle('edit', fn (CommunicationMethods $communicationMethods) => sprintf('Editing <b>%s</b>', $communicationMethods->getMethod()))
            ->setEntityLabelInSingular('Communication Method')
            ->setEntityLabelInPlural('Communication Methods');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            TextField::new('method', 'Communication Method')->setColumns(12),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
        ];
    }
}
