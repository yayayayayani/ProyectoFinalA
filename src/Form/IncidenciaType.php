<?php

namespace App\Form;

use App\Entity\{Jugador, Jornada, Gol, Incidencia};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{DateType, IntegerType, ChoiceType, TextareaType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncidenciaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jugador', EntityType::class, ['class' => Jugador::class, 'placeholder' => 'Selecciona un jugador'])
            ->add('descripcion', TextareaType::class, ['label' => 'Descripción', 'attr' => ['rows' => 4]])
            ->add('tipoTarjeta', ChoiceType::class, ['label' => 'Tipo de tarjeta', 'placeholder' => 'Selecciona una tarjeta', 'choices' => ['Amarilla' => 'amarilla', 'Roja' => 'roja']])
            ->add('fechaIncidencia', DateType::class, ['label' => 'Fecha de incidencia', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
            ->add('fechaSuspension', DateType::class, ['label' => 'Fecha de suspensión', 'widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false, 'help' => 'Obligatoria para tarjeta roja. El jugador no podrá participar en jornadas de esta fecha.']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Incidencia::class]);
    }
}
