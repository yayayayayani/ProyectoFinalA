<?php

namespace App\Controller;

use App\Entity\Jugador;
use App\Entity\Equipo;
use App\Entity\Gol;
use App\Entity\Incidencia;
use App\Service\EstadisticasTorneo;
use App\Form\JugadorType;
use App\Repository\JugadorRepository;
use App\Service\FotografiaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/jugador')]
final class JugadorController extends AbstractController
{
    #[Route(name: 'app_jugador_index', methods: ['GET'])]
    public function index(JugadorRepository $jugadorRepository, Request $request, EntityManagerInterface $em): Response
    {
        $busqueda = trim($request->query->getString('q'));
        $equipoId = $request->query->getInt('equipo');
        $qb = $jugadorRepository->createQueryBuilder('j')->addSelect('e')->join('j.equipo', 'e')->orderBy('j.apellidos', 'ASC');
        if ($busqueda !== '') {
            $qb->andWhere('LOWER(j.nombres) LIKE :q OR LOWER(j.apellidos) LIKE :q')->setParameter('q', '%'.mb_strtolower($busqueda).'%');
        }
        if ($equipoId > 0) $qb->andWhere('e.id = :equipo')->setParameter('equipo', $equipoId);

        return $this->render('jugador/index.html.twig', [
            'jugadors' => $qb->getQuery()->getResult(),
            'equipos' => $em->getRepository(Equipo::class)->findBy([], ['nombre' => 'ASC']),
            'busqueda' => $busqueda, 'equipoId' => $equipoId,
        ]);
    }

    #[Route('/new', name: 'app_jugador_new', methods: ['GET', 'POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, FotografiaUploader $uploader): Response
    {
        $jugador = new Jugador();
        $form = $this->createForm(JugadorType::class, $jugador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->uploadFotografia($form, $jugador, $uploader)) {
            $entityManager->persist($jugador);
            $entityManager->flush();

            $this->addFlash('success', 'Jugador registrado correctamente.');

            return $this->redirectToRoute('app_jugador_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('jugador/new.html.twig', [
            'jugador' => $jugador,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_jugador_show', methods: ['GET'])]
    public function show(Jugador $jugador, EntityManagerInterface $em, EstadisticasTorneo $estadisticas): Response
    {
        return $this->render('jugador/show.html.twig', [
            'jugador' => $jugador,
            'goles' => $em->getRepository(Gol::class)->findBy(['jugador' => $jugador]),
            'incidencias' => $estadisticas->incidencias(null, $jugador),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_jugador_edit', methods: ['GET', 'POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Jugador $jugador, EntityManagerInterface $entityManager, FotografiaUploader $uploader): Response
    {
        $form = $this->createForm(JugadorType::class, $jugador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->uploadFotografia($form, $jugador, $uploader)) {
            $entityManager->flush();
            $this->addFlash('success', 'Jugador actualizado correctamente.');

            return $this->redirectToRoute('app_jugador_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('jugador/edit.html.twig', [
            'jugador' => $jugador,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/fotografia', name: 'app_jugador_fotografia', methods: ['GET'])]
    public function fotografia(Jugador $jugador, FotografiaUploader $uploader): BinaryFileResponse
    {
        $path = $uploader->path($jugador->getFotografia());
        if ($path === null) {
            throw $this->createNotFoundException('Fotografía no disponible.');
        }

        $response = $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();

        return $response;
    }

    private function uploadFotografia(FormInterface $form, Jugador $jugador, FotografiaUploader $uploader): bool
    {
        $file = $form->get('fotografiaArchivo')->getData();
        if ($file === null) {
            return true;
        }

        try {
            $filename = $uploader->upload($file);
        } catch (FileException) {
            $form->get('fotografiaArchivo')->addError(new FormError('No se pudo guardar la fotografía. Inténtalo de nuevo.'));

            return false;
        }

        // Solo cambiamos la referencia después de guardar el nuevo archivo.
        // Las fotografías anteriores se conservan para evitar borrar archivos existentes.
        $jugador->setFotografia($filename);

        return true;
    }

    #[Route('/{id}', name: 'app_jugador_delete', methods: ['POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Jugador $jugador, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_jugador_'.$jugador->getId(), $request->getPayload()->getString('_token'))) {
            if ($entityManager->getRepository(Gol::class)->count(['jugador' => $jugador]) > 0
                || $entityManager->getRepository(Incidencia::class)->count(['jugador' => $jugador]) > 0) {
                $this->addFlash('error', 'Este jugador tiene goles o incidencias. Conserva su historial o elimina primero esos registros.');

                return $this->redirectToRoute('app_jugador_show', ['id' => $jugador->getId()], Response::HTTP_SEE_OTHER);
            }
            $entityManager->remove($jugador);
            $entityManager->flush();
            $this->addFlash('success', 'Jugador eliminado.');
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Token de seguridad inválido.');
        }

        return $this->redirectToRoute('app_jugador_index', [], Response::HTTP_SEE_OTHER);
    }
}
