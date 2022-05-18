<?php

namespace App\Form;

use App\Entity\Countries;
use App\Entity\Distributors;
use Doctrine\ORM\EntityManagerInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

class DistributorFormType extends AbstractType
{
    private $em;
    private $token;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $token)
    {
        $this->em = $em;
        $this->token = $token;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countries = $this->em->getRepository(Countries::class)->findAll();
        $country_id = $this->token->getToken()->getUser()->getDistributor()->getAddressCountry()->getId();

        $arr[''] = 'Select Your Country';

        foreach($countries as $country){

            $arr[$country->getId()] = $country->getName();
        }

        $builder
            ->add('distributorName', TextType::class, [
                'label' => 'Company Name',
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
            ->add(
                'addressCountry',
                ChoiceType::class,
                [
                    'label' => 'Select a Country',
                    'choices' => array_flip($arr),
                    'required' => false,
                    'data' => $country_id,
                ]
            )
            ->add('addressStreet', TextType::class, [
                'label' => 'Street Address',
                'required' => false,
            ])
            ->add('addressCity', TextType::class, [
                'label' => 'City',
                'required' => false,
            ])
            ->add('addressPostalCode', TextType::class, [
                'label' => 'Postal Code',
                'required' => false,
            ])
            ->add('addressState', TextType::class, [
                'label' => 'State',
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
                'logo',
                FileType::class,
                [
                    'required' => false,
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
