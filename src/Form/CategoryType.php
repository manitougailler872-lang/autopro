<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Libellé'])
            ->add('description', TextType::class, ['label' => 'Description courte', 'required' => false])
            ->add('icon', ChoiceType::class, [
                'label' => 'Icône',
                'choices' => [
                    'Voiture'    => 'car',
                    'Utilitaire' => 'van',
                    'Moto'       => 'bike',
                    'SUV / 4x4'  => 'suv',
                ],
            ])
            ->add('position', IntegerType::class, ['label' => 'Ordre d\'affichage', 'required' => false])
            ->add('active', CheckboxType::class, ['label' => 'Active', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Category::class]);
    }
}
