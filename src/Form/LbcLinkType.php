<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Brand;
use App\Entity\LbcLink;
use App\Service\FileUploadService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LbcLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', TextType::class, ['label' => 'Nom du client'])
            ->add('you', TextType::class, ['label' => 'Non Profile Leboncoin', 'required' => false])
            ->add('referenceLbc', TextType::class, ['label' => 'Référence Leboncoin', 'required' => false])
            ->add('price', TextType::class, ['label' => 'Prix payé', 'required'=> false])
            ->add('reservationNumber', TextType::class, ['label' => 'Référence de la réservation', 'required'=> false,
            'mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => LbcLink::class, 'csrf_token_id' => '_lbclink',]);
    }
}
