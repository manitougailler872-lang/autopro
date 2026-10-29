<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RefundRequestType extends AbstractType
{
    public const REASONS = [
        'Véhicule ne correspondant pas à mes attentes' => 'not_as_described',
        'Informations incorrectes'                      => 'wrong_info',
        'Problème avec la réservation'                  => 'reservation_issue',
        'Délai de livraison'                            => 'delivery_delay',
        'Changement d\'avis'                            => 'changed_mind',
        'Autre'                                         => 'other',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Motif',
                'choices' => self::REASONS,
                'placeholder' => 'Sélectionnez un motif',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('customerComment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 4],
                'constraints' => [new Assert\Length(max: 2000)],
            ])
            ->add('iban', TextType::class, [
                'label' => 'IBAN de remboursement',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Iban(message: 'IBAN invalide.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'refund_request',
        ]);
    }
}
