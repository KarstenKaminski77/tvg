<?php

namespace App\Form;

use App\Entity\Addresses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressesFormType extends AbstractType
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clinicName', TextType::class, [
                'label' => 'Clinic Name*',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Phone Number*',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Address Line 1*',
                'required' => false,
            ])
            ->add('suite', TextType::class, [
                'label' => 'APT / Suite',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Postal Code*',
                'required' => false,
            ])
            ->add('state', TextType::class, [
                'label' => 'State*',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'City*',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Addresses::class,
        ]);
    }
}
