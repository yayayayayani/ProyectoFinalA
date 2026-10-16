<?php

namespace App\Controller;

use App\Entity\{Equipo, Jugador, Jornada};
use App\Service\EstadisticasTorneo;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotNull;

final class ReporteController extends AbstractController
{
    #[Route('/reportes/{tipo}', name: 'app_reporte', requirements: ['tipo' => 'arbitro|incidencias|goleadores'], methods: ['GET'])]
    public function index(string $tipo, Request $request, EstadisticasTorneo $estadisticas): Response
    {
        $builder = $this->createFormBuilder(null, ['method' => 'GET', 'csrf_protection' => false])
            ->add('equipo', EntityType::class, ['class' => Equipo::class, 'choice_label' => 'nombre', 'required' => false, 'placeholder' => 'Todos los equipos']);
        if ($tipo === 'incidencias') {
            $builder->add('jugador', EntityType::class, ['class' => Jugador::class, 'required' => false, 'placeholder' => 'Todos los jugadores']);
        } else {
            $builder->add('jornada', EntityType::class, [
                'class' => Jornada::class, 'required' => $tipo === 'arbitro',
                'placeholder' => $tipo === 'arbitro' ? 'Selecciona la jornada a jugar' : 'Todas las jornadas',
                'constraints' => $tipo === 'arbitro' ? [new NotNull(message: 'Selecciona una jornada para consultar las suspensiones.')] : [],
            ]);
        }
        $form = $builder->getForm();
        $form->handleRequest($request);
        $filtros = $form->getData() ?? [];
        if ($form->isSubmitted() && $form->isValid() && $tipo === 'incidencias'
            && ($filtros['equipo'] ?? null) && ($filtros['jugador'] ?? null)
            && $filtros['jugador']->getEquipo()->getId() !== $filtros['equipo']->getId()) {
            $form->get('jugador')->addError(new FormError('El jugador no pertenece al equipo seleccionado.'));
        }
        $filas = [];
        $listo = (!$form->isSubmitted() && $tipo !== 'arbitro') || ($form->isSubmitted() && $form->isValid());
        if ($listo) {
            $equipo = $filtros['equipo'] ?? null;
            $filas = match ($tipo) {
                'arbitro' => $estadisticas->nomina($equipo, $filtros['jornada']),
                'incidencias' => $estadisticas->incidencias($equipo, $filtros['jugador'] ?? null),
                'goleadores' => $estadisticas->goleadores($equipo, $filtros['jornada'] ?? null),
            };
        }

        return $this->render('reporte/index.html.twig', compact('tipo', 'form', 'filas', 'filtros', 'listo'));
    }
}
