<?php

namespace App\Form;

use App\Entity\Equipo;
use App\Entity\Jugador;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class JugadorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombres', null, ['empty_data' => '', 'attr' => [
                'maxlength' => 100, 'pattern' => Jugador::NOMBRE_PATTERN,
                'title' => 'Solo letras, espacios, guiones o apóstrofes.',
            ]])
            ->add('apellidos', null, ['empty_data' => '', 'attr' => [
                'maxlength' => 100, 'pattern' => Jugador::NOMBRE_PATTERN,
                'title' => 'Solo letras, espacios, guiones o apóstrofes.',
            ]])
            ->add('fechaNacimiento', null, [
                'label' => 'Fecha de nacimiento',
                'widget' => 'single_text',
                'attr' => ['max' => (new \DateTimeImmutable('today -6 years'))->format('Y-m-d')],
                'help' => 'El jugador debe tener al menos 6 años cumplidos.',
                'invalid_message' => 'Ingresa una fecha de nacimiento válida.',
            ])
            ->add('fotografiaArchivo', FileType::class, [
                'label' => 'Fotografía',
                // La entidad guarda el nombre; este campo recibe el archivo temporal.
                'mapped' => false,
                'required' => false,
                'help' => 'JPEG, PNG o WebP, máximo 2 MB y 4096 × 4096 píxeles. Déjalo vacío para conservar la foto actual.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                'constraints' => [new Image(
                    maxSize: '2M',
                    extensions: ['jpg', 'jpeg', 'png', 'webp'],
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    maxWidth: 4096,
                    maxHeight: 4096,
                    maxSizeMessage: 'La fotografía no debe superar 2 MB.',
                    extensionsMessage: 'Selecciona una imagen JPEG, PNG o WebP válida.',
                    mimeTypesMessage: 'Selecciona una imagen JPEG, PNG o WebP válida.',
                    sizeNotDetectedMessage: 'No se pudo leer la imagen.',
                    maxWidthMessage: 'El ancho máximo es de 4096 píxeles.',
                    maxHeightMessage: 'La altura máxima es de 4096 píxeles.',
                )],
            ])
            ->add('equipo', EntityType::class, [
                'class' => Equipo::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Selecciona un equipo',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jugador::class,
        ]);
    }
}
