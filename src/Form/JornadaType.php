<?php

namespace App\Form;

use App\Entity\{Jugador, Jornada, Gol, Incidencia};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{DateType, IntegerType, ChoiceType, TextareaType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class JornadaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', null, ['label' => 'Nombre de la jornada', 'empty_data' => ''])
            ->add('fecha', DateType::class, ['label' => 'Fecha de juego', 'widget' => 'single_text', 'input' => 'datetime_immutable']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Jornada::class]);
    }
}
