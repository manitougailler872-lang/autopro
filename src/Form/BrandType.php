<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Brand;
use App\Service\FileUploadService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BrandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Libellé'])
            ->add('position', IntegerType::class, ['label' => 'Ordre d\'affichage', 'required' => false])
            ->add('active', CheckboxType::class, ['label' => 'Active', 'required' => false])
            ->add('isModel', CheckboxType::class, ['label' => 'Est-ce un modèle?', 'required'=> false])
            ->add('forPiece', CheckboxType::class, ['label' => 'Pour les pièces', 'required' => false])
            ->add('logo', FileType::class, [
                'label'    => 'Logo (JPG, PNG, WEBP · 2 Mo max)',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '2M',
                        mimeTypes: array_keys(FileUploadService::IMAGE_MIME_TYPES),
                        mimeTypesMessage: 'Format non autorisé.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Brand::class, 'csrf_token_id' => '_brand',]);
    }
}
