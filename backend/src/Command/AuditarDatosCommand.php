<?php

namespace App\Command;

use App\Entity\{Equipo, Jugador, Jornada, Gol, Incidencia};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:auditar-datos', description: 'Revisa las reglas actuales sin modificar registros ni mostrar datos personales.')]
final class AuditarDatosCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ValidatorInterface $validator) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $total = $invalid = 0;
        foreach ([Equipo::class, Jugador::class, Jornada::class, Gol::class, Incidencia::class] as $type) {
            foreach ($this->em->getRepository($type)->createQueryBuilder('r')->getQuery()->toIterable() as $entity) {
                ++$total;
                $errors = $this->validator->validate($entity);
                if (count($errors) === 0) continue;
                ++$invalid;
                $fields = [];
                foreach ($errors as $error) $fields[] = $error->getPropertyPath();
                $io->writeln((new \ReflectionClass($type))->getShortName().' #'.$entity->getId().': '.implode(', ', array_unique($fields)));
            }
        }
        $io->writeln(sprintf('Revisados: %d. Registros con incumplimientos: %d. No se modificaron datos.', $total, $invalid));
        return $invalid ? Command::FAILURE : Command::SUCCESS;
    }
}
