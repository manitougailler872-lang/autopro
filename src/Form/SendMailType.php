<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Mail;
use App\Entity\Vehicle;
use App\Entity\Reservation;
use App\Enum\GenderEnum;
use App\Enum\MailType;
use App\Enum\VehicleTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SendMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gender', EnumType::class, [
            'class' => GenderEnum::class,
            'label' =>  'Madame/Monsieur',
            'mapped' =>  false,
             'required'=> false,
            'expanded' => false,
            'multiple' => false,
            'choice_label' => static fn (GenderEnum $provider): string => $provider->label(),
            'constraints' => [
                new Assert\NotNull(),
            ],
            'attr' => [
                'required'=> false,
            ],
        ]);
        $builder->add('email', EmailType::class, [
            'label' => 'Email du client',
            'required'=> false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
            'attr' => [
                'required'=> false,
            ],
        ]);
        $builder->add('orderNumber', TextType::class, [
            'label' => 'Saisir le numéro du contrat',
            'required'=> false,
            'mapped'=>false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
            'attr' => [
                'required'=> false,
            ],
        ]);
        $builder->add('vehicle', EntityType::class, [
           'label' => 'Véhicule concerné (si information complétaire)',
            'class' => Vehicle::class,
            'choice_label' => function (Vehicle $vehicle): string {
                return sprintf(
                    '%s %s - %s (%s)',
                    $vehicle->getBrand(),
                    $vehicle->getModel(),
                    $vehicle->getYear(),
                    $vehicle->getVehicleType() == VehicleTypeEnum::PIECE ? 'P':'V'
                );
            },
            'required'=> false,
            'constraints' => [
                new Assert\NotBlank()
            ],
            'attr' => [
                'required'=> false,
            ],
            'placeholder' => 'Sélectionnez un véhicule',
        ]);

        $builder->add('reservation', EntityType::class, [
           'label' => 'Réservation (si contrat de vente)',
            'class' => Reservation::class,
            'choice_label' => function (Reservation $reservation): string {
                return sprintf(
                    '%s (%s €) - %s (%s)',
                    $reservation->getVehicle()->getBrand(),
                    $reservation->getVehicle()->getPrice(),
                    $reservation->getCustomerName(),
                    $reservation->getReservationNumber(),
                );
            },
            'attr' => [
                'required'=> false,
            ],
            'constraints' => [
                new Assert\NotBlank()
            ],
            'required'=> false,
            'placeholder' => 'Sélectionnez une réservation',
        ]);
        $builder->add('mailType', EnumType::class, [
            'class' => MailType::class,
                'label' =>  ' ',
                'expanded' => true,
                'required'=> false,
                'multiple' => false,
                'choice_label' => static fn (MailType $provider): string => $provider->label(),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ]);
        $builder->add('smsNotifierEnable', CheckboxType::class, ['label' => 'Alerte via sms?', 'required'=> false]);
        $builder->add('generateContrat', CheckboxType::class, ['label' => 'Générer contrat?', 'required'=> false,
        'mapped'=> false]);
        $builder->add('sendContrat', CheckboxType::class, ['label' => 'Envoyer le contrat?', 'required'=> false,   'mapped'=> false]);
        $builder->add('contrat', FileType::class, ['label' => 'Téléverser le contrat', 'required'=> false]);
        $builder->add('dateEtablissementContrat', TextType::class, ['label' => 'Date établissement contrat', 'required'=> false]);
        $builder->add('heureContrat', TextType::class, ['label' => 'Heure établissement contrat', 'required'=> false]);
        $builder->add('minuteContrat', TextType::class, ['label' => 'Minute établissement contrat', 'mapped'=> false]);
        $builder->add('priceConcluded', TextType::class, ['label' => 'Prix conclus', 'mapped'=> false]);
        $builder->add('cardIdNumber', TextType::class, ['label' => 'Numéro pièce du client', 'required'=> false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mail::class, 
            'csrf_token_id' => '_mail',
        ]);
    }
}
