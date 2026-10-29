<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Vehicle;
use App\Enum\FuelType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand',       TextType::class,  ['label' => 'Libellé', 'required' => false])
            ->add('ean',       TextType::class,  ['label' => 'EAN', 'required' => false])
            ->add('wvaNumber',       TextType::class,  ['label' => 'Numéro WVA', 'required' => false])
            ->add('thickness',       TextType::class,  ['label' => 'Épaisseur 1 [mm]', 'required' => false])
            ->add('thicknessTwo',       TextType::class,  ['label' => 'Épaisseur 2 [mm]', 'required' => false])
            ->add('width',       TextType::class,  ['label' => 'Largeur 1', 'required' => false])
            ->add('widthTwo',       TextType::class,  ['label' => 'Largeur 2', 'required' => false])
            ->add('height',       TextType::class,  ['label' => 'Hauteur 1', 'required' => false])
            ->add('heightTwo',       TextType::class,  ['label' => 'Hauteur 2', 'required' => false])
            ->add('systemDeFreinage',       TextType::class,  ['label' => 'Système de freinage', 'required' => false])
            ->add('compatibility',       TextType::class,  ['label' => 'Compatibilité', 'required' => false])
            ->add('originNumber',       TextareaType::class,  ['label' => 'Numéro d\'origine', 'required' => false])
            ->add('garnitureMaterial',       TextType::class,  ['label' => 'Matériel de garniture', 'required' => false])
            ->add('interior',       TextareaType::class,  ['label' => 'Intérieur', 'required' => false])
            ->add('compatibility',       TextareaType::class,  ['label' => 'Compatibilité', 'required' => false])
            ->add('history',       TextareaType::class,  ['label' => 'Historique', 'required' => false])
            ->add('exterior',       TextareaType::class,  ['label' => 'Extérieur', 'required' => false])
            ->add('dateEntretien',       TextType::class,  ['label' => 'Date entretien', 'required' => false])
            ->add('ctValidity',       TextType::class,  ['label' => 'Date de validité controle', 'required' => false])
            ->add('controlDate',       TextType::class,  ['label' => 'Date de controle', 'required' => false])
            ->add('rapportEntretien',       TextType::class,  ['label' => 'Rapport entretien', 'required' => false])
            ->add('datePremiereMiseEnCirculation',       TextType::class,  ['label' => 'Date de prémière mise en circulation', 'required' => false])
            ->add('heureEntretien',       TextType::class,  ['label' => 'Heure entretien', 'required' => false])
            ->add('security',       TextareaType::class,  ['label' => 'Sécurité', 'required' => false])
            ->add('nbOwner',       NumberType::class,  ['label' => 'Nombre de propriétaire', 'required' => false])
            ->add('model',       TextType::class,  ['label' => 'Modèle', 'required' => false])
            ->add('equipments',       TextareaType::class,  ['label' => 'Équipements', 'required' => false])
            ->add('version',     TextType::class,  ['label' => 'Version', 'required' => false])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie', 'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => [
                    'Citadine' => 'Citadine',
                    'Berline' => 'Berline',
                    'SUV' => 'SUV',
                    '4x4' => '4x4',
                    'Utilitaire' => 'Utilitaire',
                    'Break' => 'Break',
                    'Camping-Car' => 'Camping-Car',
                    'Coupé' => 'Coupé',
                    'Fourgon' => 'Fourgon',
                    'Pick-Up'=> 'Pick-Up',
                    'Sport' => 'Sport',
                    'Van' => 'Van'
                ],
            ])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 5]])
            ->add('year',    IntegerType::class, ['label' => 'Année'])
            ->add('mileage', IntegerType::class, ['label' => 'Kilométrage (km)'])
            ->add('fuel', EnumType::class, [
                'class' => FuelType::class,
                'label' => 'Carburant',
                'choice_label' => static fn (FuelType $fuel): string => $fuel->label(),
                'placeholder' => 'Sélectionnez un carburant',
            ])
            ->add('phoneCallInformation', TextareaType::class, ['label' => 'Informations Appel', 'required' => false])
            ->add('interiorMail', TextareaType::class, ['label' => 'Intérieur e-mail', 'required' => false])
            ->add('exteriorMail', TextareaType::class, ['label' => 'Extérieur e-mail', 'required' => false])
            ->add('securityMail', TextareaType::class, ['label' => 'Sécurité e-mail', 'required' => false])
            ->add('historiqueMail', TextareaType::class, ['label' => 'Historiques e-mail', 'required' => false])
            ->add('technicalInformation', TextareaType::class, ['label' => 'Informations Technique', 'required' => false])
            ->add('firstImmatriculationDate', TextType::class, ['label' => 'Date de première immatriculation', 'required' => false])
            ->add('vim', TextType::class, ['label' => 'Vim', 'required' => false])
            ->add('limitValiditeControl', TextType::class, ['label' => 'Limite validation controle', 'required' => false])
            ->add('kilometrageReleve', NumberType::class, ['label' => 'Kilométrage révélé', 'required' => false])
            ->add('emissionClass', TextType::class, ['label' => 'Classe d\'emission CO2', 'required' => false])
            ->add('co2', TextType::class, ['label' => 'CO2', 'required' => false])
            ->add('existenceVerifiee', CheckboxType::class, ['label' => 'Existance vérifiée', 'required' => false])
            ->add('aucunSinistre', CheckboxType::class, ['label' => 'Aucun sinistre', 'required' => false])
            ->add('usageProfessionnel', CheckboxType::class, ['label' => 'Usage Pro', 'required' => false])
            ->add('isAuction', CheckboxType::class, ['label' => 'En enchère', 'required' => false])
            ->add('power', TextType::class, ['label' => 'Puissance (ch)', 'required' => false])
            ->add('powerCv', TextType::class, ['label' => 'Puissance (cv)', 'required' => false])
            ->add('color', TextType::class, ['label' => 'Couleur', 'required' => false])
            ->add('financialPrice', TextType::class, ['label' => 'Prix de financement', 'required' => false])
            ->add('doors', IntegerType::class, ['label' => 'Nombre de portes', 'required' => false])
            ->add('places', IntegerType::class, ['label' => 'Nombre de places', 'required' => false])
            ->add('immatriculation', TextType::class, ['label' => 'Immatriculation', 'required' => false])
            ->add('cylinder', TextType::class, ['label' => 'Cylindrée', 'required' => false])
            ->add('ctFile', FileType::class, ['label' => 'Controle Technique', 'mapped'=> false, 'required' => false])
            ->add('transmission', ChoiceType::class, [
                'label' => 'Boîte de vitesses', 'required' => false,
                'placeholder' => 'Sélectionner...',
                'choices' => ['Manuelle' => 'Manuelle', 'Automatique' => 'Automatique'],
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Notation (étoiles)', 'required' => false,
                'placeholder' => 'Non noté',
                'choices' => ['★' => 1, '★★' => 2, '★★★' => 3, '★★★★' => 4, '★★★★★' => 5],
            ])
            ->add('price', MoneyType::class, [
                'label'    => 'Prix',
                'currency' => 'EUR',
                'scale'    => 2,
            ])
            ->add('vehicleType', HiddenType::class, [
                'data' => $options['vehicleType'],
            ])
            ->add('published', CheckboxType::class, [
                'label'    => 'Publié sur le site',
                'required' => false,
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label'    => 'Est disponible?',
                'required' => false,
            ])
            ->add('showInFirst', CheckboxType::class, [
                'label'    => 'En honneur?',
                'required' => false,
            ])
            ->add('conformityBrand', TextType::class, [
                'label'    => 'Marque de conformité',
                'required' => false,
            ])
            ->add('bestDeal', CheckboxType::class, [
                'label'    => 'Est-ce une bonne affaire ?',
                'required' => false,
            ])
            ->add('reserved', CheckboxType::class, [
                'label'    => 'Est-ce réservé ?',
                'required' => false,
            ])
            ->add('bestDealPercent', IntegerType::class, [
            'label'    => 'Pourcentage de réduction ?',
            'required' => false,
        ])
        ->add('cnit', TextType::class, ['label' => 'Type/CNIT', 'required' => false])
        ->add('dimPneus', TextType::class, ['label' => 'Dimensions Pneus', 'required' => false])
        ->add('tvv', TextType::class, ['label' => 'TVV', 'required' => false])
        ->add('genreV', TextType::class, ['label' => 'Genre V', 'required' => false])
        ->add('genreVGC', TextType::class, ['label' => 'Genre VGC', 'required' => false])
        ->add('normeEuro', TextType::class, ['label' => 'Norme Euro', 'required' => false])
        ->add('genreVEuro', TextType::class, ['label' => 'Genre V Euro', 'required' => false])
        ->add('classeEnvironnementale', TextType::class, ['label' => 'Classe environnemental', 'required' => false])
        ->add('codeMoteur', TextType::class, ['label' => 'Code moteur', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Vehicle::class, 'vehicleType' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'vehicle',]);
    }
}
