<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\SettingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Identité
        $builder
            ->add(SettingService::COMPANY_NAME,         TextType::class,     ['label' => 'Nom société', 'required' => false])
            ->add(SettingService::COMPANY_ADDRESS,      TextareaType::class, ['label' => 'Adresse', 'required' => false, 'attr' => ['rows' => 2]])
            ->add(SettingService::COMPANY_EMAIL,        EmailType::class,    ['label' => 'E-mail public', 'required' => false])
            ->add(SettingService::COMPANY_PHONE,        TextType::class,     ['label' => 'Téléphone', 'required' => false])
            ->add(SettingService::COMPANY_CP,        TextType::class,     ['label' => 'Code Postale', 'required' => false])
            ->add(SettingService::COMPANY_CITY,        TextType::class,     ['label' => 'Ville', 'required' => false])
            ->add(SettingService::OPENING_HOUR,        TextareaType::class,     ['label' => 'Horaire d\'ouverture', 'required' => false])
            ->add(SettingService::COMPANY_REGISTRATION, TextType::class,     ['label' => 'SIRET / immatriculation', 'required' => false])

            // Réservation
            ->add(SettingService::RESERVATION_PERCENTAGE, NumberType::class, [
                'label' => 'Pourcentage frais de réservation (%)',
                'scale' => 2,
                'constraints' => [new Assert\NotBlank(), new Assert\Range(min: 0, max: 100)],
            ])

            // Banque
            ->add(SettingService::BANK_HOLDER,          TextType::class, ['label' => 'Titulaire du compte', 'required' => false])
            ->add(SettingService::BANK_NAME,            TextType::class, ['label' => 'Banque', 'required' => false])
            ->add(SettingService::BANK_IBAN,            TextType::class, ['label' => 'IBAN', 'required' => false])
            ->add(SettingService::BANK_BIC,             TextType::class, ['label' => 'BIC / SWIFT', 'required' => false])
            ->add(SettingService::BANK_REFERENCE_PREFIX,TextType::class, ['label' => 'Préfixe de référence', 'required' => false])
            ->add(SettingService::PAYMENT_MESSAGE,      TextareaType::class, ['label' => 'Instructions de paiement', 'required' => false, 'attr' => ['rows' => 4]])
            ->add(SettingService::EXPEDITEUR_PAR_DEFAULT,      TextType::class, ['label' => 'Expediteur par défaut', 'required' => false, 'attr' => []])

            // Réseaux sociaux
            ->add(SettingService::TIKTOK_URL,           UrlType::class,   ['label' => 'URL TikTok', 'required' => false])
            ->add(SettingService::TIKTOK_TEXT,          TextType::class,  ['label' => 'Texte du bouton TikTok', 'required' => false])
            ->add(SettingService::ACTIVE_SMS,  ChoiceType::class,  ['choices' => [
    'Oui' => 'true',
    'Non' => 'false',
],'label' => 'Activé l\'envoie des sms', 'required' => false])

            // Informations légales
            ->add(SettingService::LEGAL_NOTICE,         TextareaType::class, ['label' => 'Mentions légales', 'required' => false, 'attr' => ['rows' => 8]])
            ->add(SettingService::TERMS,                TextareaType::class, ['label' => 'Conditions générales', 'required' => false, 'attr' => ['rows' => 8]])
            ->add(SettingService::PRIVACY_POLICY,       TextareaType::class, ['label' => 'Politique de confidentialité', 'required' => false, 'attr' => ['rows' => 8]])
            ->add(SettingService::COMPANY_LOGO,         FileType::class, ['label' => 'Logo de la société', 'mapped' =>  false, 'required' => false, 'attr' => ['rows' => 8]])
            ->add(SettingService::COMPANY_SITE,         UrlType::class, ['label' => 'Lien du site',  'required' => false, 'attr' => ['rows' => 8]])
            ->add(SettingService::CONTRAT_SIGNATURE,         FileType::class, ['label' => 'Signature de contrat vente', 'mapped' =>  false, 'required' => false, 'attr' => ['rows' => 8]])
            ->add(SettingService::PAYMENT_MESSAGE_V1,   TextareaType::class, ['label' => 'Instructions de paiement pour auto', 'required' => false, 'attr' => ['rows' => 4]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'settings',
            'attr'          => ['novalidate' => 'novalidate'],
        ]);
    }
}
