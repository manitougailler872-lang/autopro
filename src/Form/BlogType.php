<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Blog;
use App\Service\FileUploadService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Titre'])
            ->add('shortDescription', TextareaType::class, ['label' => 'Courte description', 'required' => false])
            ->add('Description', TextareaType::class, ['label' => 'Description', 'required' => false])
            ->add('showInFront', CheckboxType::class, ['label' => 'Description', 'required' => false])
            ->add('active', CheckboxType::class, ['label' => 'Active', 'required' => false])
            ->add('frontImage', FileType::class, [
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
        $resolver->setDefaults(['data_class' => Blog::class, 'csrf_token_id' => '_blog',]);
    }
}
