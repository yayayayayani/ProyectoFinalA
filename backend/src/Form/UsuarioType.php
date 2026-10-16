<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, PasswordType, RepeatedType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('usuario', TextType::class, [
                'label' => 'Nombre de usuario',
                'help' => 'Entre 3 y 60 letras, números, puntos, guiones o guiones bajos.',
                'attr' => ['autocomplete' => 'off', 'maxlength' => 60],
                'constraints' => [new Assert\NotBlank(), new Assert\Regex(
                    pattern: '/^[a-zA-Z0-9_.-]{3,60}$/D',
                    message: 'Usa entre 3 y 60 letras, números, puntos, guiones o guiones bajos.',
                )],
            ])
            ->add('rol', ChoiceType::class, [
                'label' => 'Rol',
                'placeholder' => 'Selecciona un rol',
                'choices' => ['Árbitro' => 'ROLE_ARBITRO', 'Administrador' => 'ROLE_ADMIN'],
                'help' => 'Árbitro: consulta información y reportes. Administrador: también registra, edita, elimina y crea usuarios.',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Contraseña'],
                'second_options' => ['label' => 'Repetir contraseña'],
                'options' => ['attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'Las contraseñas no coinciden.',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 12, max: 4096,
                    minMessage: 'Usa al menos {{ limit }} caracteres.')],
            ]);
    }
}
