<?php

namespace App\Form;

use App\Entity\DistributorProducts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistributorProductsFormType extends AbstractType
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', HiddenType::class)
            ->add('distributor', HiddenType::class)
            ->add('sku', TextType::class, [
                'label' => 'SKU',
                'required' => false,
            ])
            ->add('distributorNo', TextType::class, [
                'label' => 'Serial No',
                'required' => false,
            ])
            ->add('unitPrice', TextType::class, [
                'label' => 'Price',
                'required' => false,
            ])
            ->add('stockCount', IntegerType::class, [
                'label' => 'Stock Level',
                'required' => false,
            ])
            ->add(
                'expiryDate',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'label' => 'Date',
                    'attr' => [
                        'class' => 'form-control input-inline datetimepicker',
                        'data-provide' => 'datepicker',
                        'data-format' => 'YYYY-mm-dd',
                    ],
                    'required' => false,
                ]
            )
            ->add(
                'taxExempt',
                ChoiceType::class,
                [
                    'choices' => [
                        'Yes' => 1,
                        'No' => 0,
                    ],
                    'placeholder' => false,
                    'required' => false,
                    'expanded' => true,
                    'attr' => [
                        'class' => 'form-check-input',
                    ]
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DistributorProducts::class,
        ]);
    }
}
