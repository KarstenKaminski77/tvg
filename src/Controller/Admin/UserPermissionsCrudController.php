<?php

namespace App\Controller\Admin;

use App\Entity\UserPermissions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserPermissionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserPermissions::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            ->setPageTitle('index', 'User Permissions')
            ->setPageTitle('new', 'New User Permission')
            ->setPageTitle('detail', fn (UserPermissions $userPermissions) => (string) $userPermissions)
            ->setPageTitle('edit', fn (UserPermissions $userPermissionss) => sprintf('Editing <b>%s</b>', $userPermissionss->getPermission()))
            ->setEntityLabelInSingular('User Permission')
            ->setEntityLabelInPlural('User Permissions');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            BooleanField::new('isClinic', 'Clinic')->setColumns(6),
            BooleanField::new('isDistributor', 'Distributor')->setColumns(6),
            TextField::new('permission', 'Permission')->setColumns(6),
            TextField::new('info', 'Description')->setColumns(6)
                ->onlyOnForms(),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
        ];
    }

}
