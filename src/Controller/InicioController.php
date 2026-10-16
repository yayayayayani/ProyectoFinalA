<?php

namespace App\Controller;

use App\Entity\Jornada;
use App\Service\EstadisticasTorneo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InicioController extends AbstractController
{
    #[Route('/', name: 'app_inicio', methods: ['GET'])]
    public function index(EstadisticasTorneo $estadisticas, EntityManagerInterface $em): Response
    {
        return $this->render('inicio/index.html.twig', [
            'resumen' => $estadisticas->resumen(),
            'goleadores' => array_slice($estadisticas->goleadores(), 0, 5),
            'incidencias' => array_slice($estadisticas->incidencias(), 0, 4),
            'jornadas' => $em->getRepository(Jornada::class)->createQueryBuilder('j')
                ->where('j.fecha >= :hoy')->setParameter('hoy', new \DateTimeImmutable('today'), 'date_immutable')
                ->orderBy('j.fecha', 'ASC')->setMaxResults(3)->getQuery()->getResult(),
        ]);
    }
}
