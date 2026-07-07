<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type'            => PasswordType::class,
            'mapped'          => false,
            'invalid_message' => 'Les mots de passe ne correspondent pas.',
            'first_options'   => ['label' => 'Nouveau mot de passe'],
            'second_options'  => ['label' => 'Confirmer le mot de passe'],
            'constraints'     => [
                new Assert\NotBlank(message: 'Veuillez saisir un mot de passe'),
                new Assert\Length(
                    min: 8,
                    minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                    max: 200
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
