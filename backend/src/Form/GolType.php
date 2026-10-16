<?php

namespace App\Form;

use App\Entity\{Jugador, Jornada, Gol, Incidencia};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{DateType, IntegerType, ChoiceType, TextareaType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class GolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jugador', EntityType::class, ['class' => Jugador::class, 'placeholder' => 'Selecciona un jugador'])
            ->add('jornada', EntityType::class, ['class' => Jornada::class, 'placeholder' => 'Selecciona una jornada'])
            ->add('cantidad', IntegerType::class, ['label' => 'Cantidad de goles', 'attr' => ['min' => 1, 'max' => 100], 'help' => 'Registra el total del jugador en esta jornada. Para corregirlo, edita este registro.']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Gol::class]);
    }
}
