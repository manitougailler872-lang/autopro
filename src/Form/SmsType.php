<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Expediteur;
use App\Entity\Reservation;
use App\Entity\Sms;
use App\Entity\Vehicle;
use App\Utils\Common;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('url', TextType::class, [
            'label' => 'Lurl à cliquer',
        ]);
        $builder->add('mailTo', TextType::class, [
            'label' => 'Mail où recevoir les accès LBC',
        ]);
        $builder->add('numero', NumberType::class, [
            'label' => 'Numéro du client (Ex: 33644658475)',
            'constraints' => [
                new Assert\NotBlank(),
            ]
        ]);
        $builder->add('referenceLbc', NumberType::class, [
            'label' => 'Référence paiement sécurisé LBC',
            'data' => Common::generateNumericCode(10)
        ]);
        $builder->add('expediteur', EntityType::class, [
           'label' => 'Choisissez l\'expéditeur',
            'class' => Expediteur::class,
           'choice_label' => 'name',
            'constraints' => [
                new Assert\NotBlank()
            ],
            'placeholder' => 'Sélectionnez un expéditeur',
            'required' => false,
        ]);
        $builder->add('message', TextareaType::class, [
            'label' => 'Sms à envoyer',
            'attr' => [
                'cols' => 10,
                'rows' => 10
            ],
            'constraints' => [
                new Assert\NotBlank(),
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sms::class, 
            'csrf_token_id' => '_mail',
        ]);
    }
}
