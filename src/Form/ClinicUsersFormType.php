<?php

namespace App\Form;

use App\Entity\ClinicUsers;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClinicUsersFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name*',
                'required' => true
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name*',
                'required' => true
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Phone Number*',
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email*',
                'required' => true
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
            'data_class' => ClinicUsers::class,
        ]);
    }
}
