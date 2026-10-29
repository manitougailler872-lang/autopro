<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SmsProvider;
use App\Enum\ProviderType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SmsProviderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
            $builder->add('provider', EnumType::class, [
                'class' => ProviderType::class,
                'label' => 'Fournisseur d\'sms',
                'expanded' => true,
                'multiple' => false,
                'choice_label' => static fn (ProviderType $provider): string => $provider->label(),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ]);
            $builder->add('credit', NumberType::class, [
                'label' => 'Crédit',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive()
                ]
            ]);
            $builder->add('baseUrl', TextType::class, [
                'label' => 'L\'url de base',
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ]);
            $builder->add('enable', CheckboxType::class, [
                'label' => 'Activé',
                'constraints' => []
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SmsProvider::class, 
            'csrf_token_id' => '_mail',
        ]);
    }
}
