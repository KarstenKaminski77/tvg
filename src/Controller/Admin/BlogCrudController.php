<?php

namespace App\Controller\Admin;

use App\Entity\Blog;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\VichImageField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BlogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Blog::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            /*IdField::new('id'),*/
            IntegerField::new('id', 'ID')->onlyOnIndex(),
            TextField::new('title'),
            TextField::new('url')->onlyOnForms(),
            AssociationField::new('blogCategory')->onlyOnForms(),
        ];
    }
}
