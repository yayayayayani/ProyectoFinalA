<?php

namespace App\Service;

use App\Entity\{Equipo, Jugador, Jornada, Gol, Incidencia};
use Doctrine\ORM\EntityManagerInterface;

final class EstadisticasTorneo
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function goleadores(?Equipo $equipo = null, ?Jornada $jornada = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('j.id, j.nombres, j.apellidos, j.fotografia, e.nombre AS equipo, SUM(g.cantidad) AS total')
            ->from(Gol::class, 'g')->join('g.jugador', 'j')->join('j.equipo', 'e')
            ->groupBy('j.id, j.nombres, j.apellidos, j.fotografia, e.nombre')
            ->orderBy('total', 'DESC')->addOrderBy('j.apellidos', 'ASC')->addOrderBy('j.id', 'ASC');
        if ($equipo) $qb->andWhere('j.equipo = :equipo')->setParameter('equipo', $equipo);
        if ($jornada) $qb->andWhere('g.jornada = :jornada')->setParameter('jornada', $jornada);

        return $qb->getQuery()->getArrayResult();
    }

    public function incidencias(?Equipo $equipo = null, ?Jugador $jugador = null): array
    {
        $qb = $this->em->createQueryBuilder()->select('i', 'j', 'e')->from(Incidencia::class, 'i')
            ->join('i.jugador', 'j')->join('j.equipo', 'e')->orderBy('i.fechaIncidencia', 'DESC')->addOrderBy('i.id', 'DESC');
        if ($equipo) $qb->andWhere('j.equipo = :equipo')->setParameter('equipo', $equipo);
        if ($jugador) $qb->andWhere('i.jugador = :jugador')->setParameter('jugador', $jugador);

        return $qb->getQuery()->getResult();
    }

    public function nomina(?Equipo $equipo, Jornada $jornada): array
    {
        $qb = $this->em->createQueryBuilder()->select('j', 'e')->from(Jugador::class, 'j')
            ->join('j.equipo', 'e')->orderBy('e.nombre', 'ASC')->addOrderBy('j.apellidos', 'ASC');
        if ($equipo) $qb->andWhere('j.equipo = :equipo')->setParameter('equipo', $equipo);
        $suspendidos = $this->em->createQueryBuilder()->select('IDENTITY(i.jugador) AS jugadorId')
            ->from(Incidencia::class, 'i')->where('i.tipoTarjeta = :tipo AND i.fechaSuspension = :fecha')
            ->setParameter('tipo', 'roja')->setParameter('fecha', $jornada->getFecha(), 'date_immutable')
            ->getQuery()->getArrayResult();
        $ids = array_column($suspendidos, 'jugadorId');

        return array_map(static fn (Jugador $j) => ['jugador' => $j, 'suspendido' => in_array($j->getId(), $ids)], $qb->getQuery()->getResult());
    }

    public function resumen(): array
    {
        return [
            'equipos' => $this->em->getRepository(Equipo::class)->count([]),
            'jugadores' => $this->em->getRepository(Jugador::class)->count([]),
            'jornadas' => $this->em->getRepository(Jornada::class)->count([]),
            'goles' => (int) $this->em->createQuery('SELECT COALESCE(SUM(g.cantidad), 0) FROM App\Entity\Gol g')->getSingleScalarResult(),
        ];
    }
}
