<?php

namespace App\Form;

use App\Entity\Distributors;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistributorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('distributorName', TextType::class, [
                'label' => 'Company Name',
                'required' => true,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Telephone',
                'required' => true,
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('website', TextType::class, [
                'label' => 'Website',
                'required' => false,
            ])
            ->add('about', TextareaType::class, [
                'label' => 'About',
                'required' => true,
            ])
            ->add('operatingHours', TextareaType::class, [
                'label' => 'Operating Hours',
                'required' => true,
            ])
            ->add('refundPolicy', TextareaType::class, [
                'label' => 'Refund Policy',
                'required' => false,
            ])
            ->add('salesTaxPolicy', TextareaType::class, [
                'label' => 'Sales Tax Policy',
                'required' => false,
            ])
            ->add('isManufaturer', ChoiceType::class, [
                'choices' => [
                    'Yes' => 1,
                    'No' => 0,
                ]
            ])
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'choices' => [
                        'Accounts' => 'ROLE_USER_',
                        'Orders' => 'ROLE_ORDERS'
                    ],
                    'data' => 'No',
                    'placeholder' => false,
                    'expanded' => true,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Distributors::class,
        ]);
    }
}
