<?php

namespace App\Command;

use App\Entity\{Equipo, Jugador, Jornada, Gol, Incidencia};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:demo', description: 'Añade datos ficticios de demostración sin borrar ni modificar los existentes.')]
final class DemoCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%jugador_fotografias_directory%')] private readonly string $photoDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->em->getRepository(Equipo::class)->findOneBy(['nombre' => 'Academia Norte · Demo'])) {
            $io->success('Los datos de demostración ya están cargados. No se hicieron cambios.');

            return Command::SUCCESS;
        }

        $this->em->wrapInTransaction(function (): void {
            $equipos = [];
            foreach (['Academia Norte', 'Halcones FC', 'Deportivo Aurora', 'Leones del Sur'] as $nombre) {
                $equipo = (new Equipo())->setNombre($nombre.' · Demo');
                $this->em->persist($equipo);
                $equipos[] = $equipo;
            }
            $jornadas = [];
            foreach (['2026-10-10', '2026-10-17', '2026-10-24', '2026-10-31'] as $n => $fecha) {
                $jornada = (new Jornada())->setNombre('Jornada '.($n + 1).' · Demo')->setFecha(new \DateTimeImmutable($fecha));
                $this->em->persist($jornada);
                $jornadas[] = $jornada;
            }
            $nombres = [
                ['Mateo', 'García'], ['Sofía', 'López'], ['Daniel', 'Pérez'],
                ['Valentina', 'Castillo'], ['Diego', 'Hernández'], ['Emilia', 'Morales'],
                ['Santiago', 'Ramírez'], ['Lucía', 'Flores'], ['Nicolás', 'Ruiz'],
                ['Camila', 'Vásquez'], ['Sebastián', 'Méndez'], ['Isabella', 'Torres'],
            ];
            $jugadores = [];
            foreach ($nombres as $n => [$nombre, $apellido]) {
                $jugador = (new Jugador())->setNombres($nombre)->setApellidos($apellido)
                    ->setFechaNacimiento(new \DateTimeImmutable(sprintf('2015-%02d-%02d', ($n % 12) + 1, 10 + $n)))
                    ->setEquipo($equipos[intdiv($n, 3)]);
                $source = $this->projectDir.'/resources/demo/jugador-'.($n + 1).'.png';
                if (is_file($source)) {
                    $filename = bin2hex(random_bytes(16)).'.png';
                    (new Filesystem())->copy($source, $this->photoDir.'/'.$filename);
                    $jugador->setFotografia($filename);
                }
                $this->em->persist($jugador);
                $jugadores[] = $jugador;
            }
            // Solo la primera jornada ya jugada tiene marcadores de ejemplo.
            foreach ([4, 2, 1, 3, 5, 1, 2, 3, 1, 2, 1, 4] as $n => $cantidad) {
                $this->em->persist((new Gol())->setJugador($jugadores[$n])->setJornada($jornadas[0])->setCantidad($cantidad));
            }
            foreach ([0, 3, 6, 9] as $n) {
                $roja = $n === 3;
                $this->em->persist((new Incidencia())->setJugador($jugadores[$n])
                    ->setDescripcion($roja ? 'Ejemplo: expulsión por una falta durante el juego.' : 'Ejemplo: amonestación por una falta durante el juego.')
                    ->setTipoTarjeta($roja ? 'roja' : 'amarilla')
                    ->setFechaIncidencia(new \DateTimeImmutable('2026-10-10'))
                    ->setFechaSuspension($roja ? new \DateTimeImmutable('2026-10-17') : null));
            }
        });
        $io->success('Se añadieron 4 equipos, 12 jugadores, 4 jornadas, 12 registros de goles y 4 incidencias ficticias. Tus datos existentes se conservaron.');

        return Command::SUCCESS;
    }
}
