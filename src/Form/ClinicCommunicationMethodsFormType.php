<?php

namespace App\Form;

use App\Entity\Addresses;
use App\Entity\ClinicCommunicationMethods;
use App\Entity\CommunicationMethods;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClinicCommunicationMethodsFormType extends AbstractType
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('communicationMethod', CommunicationMethodsFormType::class, [
                'label' => 'Clinic Name*',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClinicCommunicationMethods::class,
        ]);
    }
}
