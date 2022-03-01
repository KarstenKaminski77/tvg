<?php

namespace App\Form;

use App\Entity\Addresses;
use App\Entity\CommunicationMethods;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunicationMethodsFormType extends AbstractType
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $communication_methods = $this->em->getRepository(CommunicationMethods::class)->findAll();
        $cm_arr[0] = 'Select a method....';

        foreach($communication_methods as $cm){

            $cm_arr[$cm->getId()] = $cm->getMethod();
        }

        $builder
            ->add(
                'clinicCommunicationMethods',
                ChoiceType::class,
                [
                    'label' => false,
                    'choices' => array_flip($cm_arr),
                    'placeholder' => false,
                    'required' => false,
                    'expanded' => false,
                    'attr' => ['class' => 'form-control'],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommunicationMethods::class,
        ]);
    }
}
