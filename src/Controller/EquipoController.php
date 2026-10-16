<?php

namespace App\Controller;

use App\Entity\Equipo;
use App\Form\EquipoType;
use App\Repository\EquipoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipo')]
final class EquipoController extends AbstractController
{
    #[Route(name: 'app_equipo_index', methods: ['GET'])]
    public function index(EquipoRepository $equipoRepository): Response
    {
        return $this->render('equipo/index.html.twig', [
            'equipos' => $equipoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_equipo_new', methods: ['GET', 'POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipo = new Equipo();
        $form = $this->createForm(EquipoType::class, $equipo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($equipo);
            $entityManager->flush();

            $this->addFlash('success', 'Equipo guardado correctamente.');

            return $this->redirectToRoute('app_equipo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipo/new.html.twig', [
            'equipo' => $equipo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipo_show', methods: ['GET'])]
    public function show(Equipo $equipo): Response
    {
        return $this->render('equipo/show.html.twig', [
            'equipo' => $equipo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipo_edit', methods: ['GET', 'POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Equipo $equipo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EquipoType::class, $equipo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_equipo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipo/edit.html.twig', [
            'equipo' => $equipo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipo_delete', methods: ['POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Equipo $equipo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipo->getId(), $request->getPayload()->getString('_token'))) {
            if (!$equipo->getJugadors()->isEmpty()) {
                $this->addFlash('error', 'El equipo tiene jugadores inscritos. Reasígnalos antes de eliminarlo.');

                return $this->redirectToRoute('app_equipo_show', ['id' => $equipo->getId()], Response::HTTP_SEE_OTHER);
            }
            $entityManager->remove($equipo);
            $entityManager->flush();
            $this->addFlash('success', 'Equipo eliminado.');
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Token de seguridad inválido.');
        }

        return $this->redirectToRoute('app_equipo_index', [], Response::HTTP_SEE_OTHER);
    }
}
