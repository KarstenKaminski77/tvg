<?php

namespace App\Form;

use App\Entity\Distributors;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => false,
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Telephone',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('website', TextType::class, [
                'label' => 'Website',
                'required' => false,
            ])
            ->add('position', TextType::class, [
                'label' => 'Position',
                'required' => false,
            ])
            ->add('about', CKEditorType::class, [
                'label' => 'About',
                'required' => false,
                'config' => [
                    'toolbar' => 'basic'
                ]
            ])
            ->add('operatingHours', CKEditorType::class, [
                'label' => 'Operating Hours',
                'required' => false,
                'config' => [
                    'toolbar' => 'basic'
                ]
            ])
            ->add('refundPolicy', CKEditorType::class, [
                'label' => 'Refund Policy',
                'required' => false,
                'config' => [
                    'toolbar' => 'basic'
                ]
            ])
            ->add('salesTaxPolicy', CKEditorType::class, [
                'label' => 'Sales Tax Policy',
                'required' => false,
                'config' => [
                    'toolbar' => 'basic'
                ]
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
            ->add(
                'logo',
                FileType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'password',
                PasswordType::class,[
                    'label' => 'Old Password',
                    'required' => false
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
