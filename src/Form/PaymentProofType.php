<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\FileUploadService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentProofType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label' => 'Preuve de virement (PDF, JPG, PNG, WEBP)',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new Assert\NotNull(message: 'Veuillez sélectionner un fichier.'),
                new Assert\File(
                    maxSize: FileUploadService::PROOF_MAX_SIZE,
                    mimeTypes: array_keys(FileUploadService::PROOF_MIME_TYPES),
                    mimeTypesMessage: 'Format non autorisé. Utilisez PDF, JPG, PNG ou WEBP.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'payment_proof',
        ]);
    }
}
