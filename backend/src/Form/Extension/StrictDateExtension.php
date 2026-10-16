<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/** Evita que PHP transforme, por ejemplo, el 30 de febrero en marzo. */
final class StrictDateExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable { return [DateType::class]; }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['widget'] !== 'single_text' || !$options['html5']) return;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $value = $event->getData();
            // NotNull/las reglas de negocio controlan los campos obligatorios.
            if ($value === null || $value === '') return;
            if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts)
                || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                $event->getForm()->addError(new FormError('Ingresa una fecha válida del calendario.'));
            }
        });
    }
}
