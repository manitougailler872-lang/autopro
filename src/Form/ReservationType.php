<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Reservation;
use App\Enum\ReceptionMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Nom et prénom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2, max: 160)],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('customerPhone', TelType::class, [
                'label' => 'Téléphone (Format: 06XXXXXXXX)',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(pattern: '/^[\d +().-]{6,30}$/', message: 'Numéro de téléphone invalide.'),
                ],
            ])
            ->add('receptionMode', EnumType::class, [
                'class' => ReceptionMode::class,
                'label' => 'Mode de réception',
                'expanded' => true,
                'multiple' => false,
                'choice_label' => static fn (ReceptionMode $mode): string => $mode->label(),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ]);

        // Attach the conditional fields dynamically once we know the reception mode
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var Reservation $data */
            $data = $event->getData();
            $this->addModeFields($event->getForm()->getConfig()->getFormFactory()
                ? $event->getForm() : $event->getForm(), $data->getReceptionMode());
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $mode = ($data['receptionMode'] ?? '') === ReceptionMode::DELIVERY->value
                ? ReceptionMode::DELIVERY
                : ReceptionMode::PICKUP;
            $this->addModeFields($event->getForm(), $mode);
        });
    }

    private function addModeFields(mixed $form, ReceptionMode $mode): void
    {

        $form->remove('pickupDate');
        $form->remove('pickupTime');
        if ($mode === ReceptionMode::PICKUP) {
            $form
                ->add('pickupDate', DateType::class, [
                    'label' => 'Date de retrait',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'constraints' => [
                        new Assert\NotNull(),
                        new Assert\GreaterThanOrEqual('today', message: 'La date doit être future.'),
                    ],
                ])
                ->add('pickupTime', TimeType::class, [
                    'label' => 'Heure de retrait',
                    'widget' => 'single_text',
                    'input' => 'string',
                    'input_format' => 'H:i',
                    'constraints' => [new Assert\NotBlank()],
                ]);
        } else {
            $form
                ->add('deliveryAddress', TextType::class, [
                    'label' => 'Adresse',
                    'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 200)],
                ])
                ->add('deliveryCity', TextType::class, [
                    'label' => 'Ville',
                    'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
                ])
                ->add('deliveryPostalCode', TextType::class, [
                    'label' => 'Code postal',
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Regex(pattern: '/^[A-Z0-9 -]{3,20}$/i', message: 'Code postal invalide.'),
                    ],
                ])
                ->add('deliveryDate', DateType::class, [
                    'label' => 'Date de livraison',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'constraints' => [
                        new Assert\NotNull(),
                        new Assert\GreaterThanOrEqual('today', message: 'La date doit être future.'),
                    ],
                ])
                ->add('deliveryTime', TimeType::class, [
                    'label' => 'Heure de livraison',
                    'widget' => 'single_text',
                    'input' => 'string',
                    'input_format' => 'H:i',
                    'constraints' => [new Assert\NotBlank()],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'csrf_token_id' => 'reservation',
        ]);
    }
}
