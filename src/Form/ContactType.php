<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'class' => 'border border-luxe-border rounded-xl px-4 py-3'
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Prénom(s)',
                'required' => true,
                'attr' => [
                    'class' => 'border border-luxe-border rounded-xl px-4 py-3'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'class' => 'border border-luxe-border rounded-xl px-4 py-3'
                ]
            ])
            ->add('phone', NumberType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => [
                    'class' => 'border border-luxe-border rounded-xl px-4 py-3'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'class' => 'md:col-span-2 border border-luxe-border rounded-xl px-4 py-3 h-36'
                ]
            ])
            ->add('asking', ChoiceType::class, [
                'label' => 'Demande d\'information',
                'required' => true,
                'attr' => [
                    'class' => 'lg:col-span-2 border border-luxe-border rounded-xl px-4 py-3'
                ],
                'choices' => [
                    'Demande d\'information' => 'Demande d\'information' ,
                    'Réservation'  =>  'Réservation',
                    'Reprise' => 'Reprise',
                    'Financement' =>  'Financement'
                ]
            ])
            ->add('file', FileType::class, [
                'label' => 'Images',
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'border border-luxe-border rounded-xl px-4 py-3'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, 
            'csrf_token_id' => '_contact'
        ]);
    }
}
