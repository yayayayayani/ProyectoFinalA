<?php

namespace App\Controller;

use App\Entity\{Jornada, Gol, Incidencia};
use App\Form\{JornadaType, GolType, IncidenciaType};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{modulo}', requirements: ['modulo' => 'jornada|gol|incidencia'])]
final class RegistroController extends AbstractController
{
    private const MODULOS = [
        'jornada' => [Jornada::class, JornadaType::class, 'Jornadas', 'jornada'],
        'gol' => [Gol::class, GolType::class, 'Goles', 'registro de goles'],
        'incidencia' => [Incidencia::class, IncidenciaType::class, 'Incidencias', 'incidencia'],
    ];

    #[Route('', name: 'app_registro_index', methods: ['GET'])]
    public function index(string $modulo, EntityManagerInterface $em): Response
    {
        [$entity, , $titulo] = self::MODULOS[$modulo];
        $qb = $em->createQueryBuilder()->select('r')->from($entity, 'r');
        if ($modulo !== 'jornada') {
            $qb->addSelect('j', 'e')->join('r.jugador', 'j')->join('j.equipo', 'e');
        }
        if ($modulo === 'gol') {
            $qb->addSelect('d')->join('r.jornada', 'd')->orderBy('d.fecha', 'DESC');
        } else {
            $qb->orderBy($modulo === 'jornada' ? 'r.fecha' : 'r.fechaIncidencia', 'DESC');
        }

        return $this->render('registro/index.html.twig', [
            'modulo' => $modulo, 'titulo' => $titulo,
            'registros' => $qb->addOrderBy('r.id', 'DESC')->getQuery()->getResult(),
        ]);
    }

    #[Route('/new', name: 'app_registro_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_registro_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function form(string $modulo, Request $request, EntityManagerInterface $em, ?int $id = null): Response
    {
        [$entity, $type, $titulo, $singular] = self::MODULOS[$modulo];
        $registro = $id === null ? new $entity() : $em->find($entity, $id);
        if (!$registro) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm($type, $registro);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($registro);
            $em->flush();
            $this->addFlash('success', 'Registro guardado correctamente.');

            return $this->redirectToRoute('app_registro_index', ['modulo' => $modulo], Response::HTTP_SEE_OTHER);
        }

        return $this->render('registro/form.html.twig', compact('modulo', 'titulo', 'singular', 'registro', 'form'));
    }

    #[Route('/{id}/delete', name: 'app_registro_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    public function delete(string $modulo, int $id, Request $request, EntityManagerInterface $em): Response
    {
        $registro = $em->find(self::MODULOS[$modulo][0], $id);
        if (!$registro) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('delete_'.$modulo.'_'.$id, $request->request->getString('_token'))) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Token de seguridad inválido.');
        }
        if ($registro instanceof Jornada && $em->getRepository(Gol::class)->count(['jornada' => $registro]) > 0) {
            $this->addFlash('error', 'La jornada tiene goles registrados. No se puede eliminar mientras tenga registros asociados.');
        } else {
            $em->remove($registro);
            $em->flush();
            $this->addFlash('success', 'Registro eliminado.');
        }

        return $this->redirectToRoute('app_registro_index', ['modulo' => $modulo], Response::HTTP_SEE_OTHER);
    }
}
