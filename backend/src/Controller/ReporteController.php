<?php

namespace App\Controller;

use App\Entity\{Equipo, Jugador, Jornada};
use App\Service\EstadisticasTorneo;
use App\Service\FotografiaUploader;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotNull;

final class ReporteController extends AbstractController
{
    #[Route('/reportes/{tipo}', name: 'app_reporte', requirements: ['tipo' => 'arbitro|incidencias|goleadores'], methods: ['GET'])]
    public function index(string $tipo, Request $request, EstadisticasTorneo $estadisticas, FotografiaUploader $fotografias): Response
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

        if ($request->query->get('formato') === 'pdf') {
            if (!$listo) {
                $this->addFlash('error', 'Selecciona filtros válidos antes de descargar el PDF.');
                return $this->render('reporte/index.html.twig', compact('tipo', 'form', 'filas', 'filtros', 'listo'), new Response(status: 422));
            }
            $pdfFotos = [];
            if ($this->isGranted('ROLE_ARBITRO') && $tipo !== 'incidencias') {
                foreach ($filas as $fila) {
                    $jugador = $tipo === 'arbitro' ? $fila['jugador'] : $fila;
                    $id = is_array($jugador) ? $jugador['id'] : $jugador->getId();
                    $nombre = is_array($jugador) ? $jugador['fotografia'] : $jugador->getFotografia();
                    if ($path = $fotografias->path($nombre)) {
                        $pdfFotos[$id] = 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
                    }
                }
            }
            $options = new Options();
            $options->setIsRemoteEnabled(false);
            $options->setIsPhpEnabled(false);
            $options->setIsJavascriptEnabled(false);
            $pdf = new Dompdf($options);
            $pdf->loadHtml($this->renderView('reporte/index.html.twig', [
                ...compact('tipo', 'filas', 'filtros', 'listo', 'pdfFotos'),
                'form' => $form->createView(), 'pdf' => true,
            ]));
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();
            return new Response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="cantera-'.$tipo.'.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        }
        return $this->render('reporte/index.html.twig', compact('tipo', 'form', 'filas', 'filtros', 'listo'));
    }
}
