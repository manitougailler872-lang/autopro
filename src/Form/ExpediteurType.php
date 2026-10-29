<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Expediteur;
use App\Enum\ProviderType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExpediteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Expéditeur',
            'constraints' => [
                new Assert\NotBlank(),
            ]
        ]);
        $builder->add('provider', EnumType::class, [
            'class' => ProviderType::class,
                'label' =>  ' ',
                'expanded' => true,
                'multiple' => false,
                'choice_label' => static fn (ProviderType $provider): string => $provider->label(),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ]);
            $builder->add('active', CheckboxType::class, ['label' => 'Activé', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expediteur::class, 
            'csrf_token_id' => '_mail',
        ]);
    }
}
