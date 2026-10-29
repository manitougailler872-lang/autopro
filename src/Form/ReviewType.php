<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, ['label' => 'Nom/Prénom'])
            ->add('rating', ChoiceType::class, [
                'label'   => 'Note',
                'choices' => ['1 étoile' => 1, '2 étoiles' => 2, '3 étoiles' => 3, '4 étoiles' => 4, '5 étoiles' => 5],
                'expanded' => true,
                'data' => 5,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'attr'  => ['rows' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'csrf_token_id' => 'review',
        ]);
    }
}
