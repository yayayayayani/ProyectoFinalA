<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-sqlite', description: 'Copia los datos del torneo desde SQLite a una base MySQL vacía, sin modificar el origen.')]
final class ImportSqliteCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('archivo', InputArgument::REQUIRED, 'Ruta al archivo SQLite que se conservará como respaldo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = realpath($input->getArgument('archivo'));
        if ($path === false || !is_file($path)) {
            $io->error('El archivo SQLite de origen no existe.');
            return Command::FAILURE;
        }
        if (!$this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
            $io->error('La conexión de destino debe ser MySQL.');
            return Command::FAILURE;
        }

        // El orden respeta las claves foráneas; nunca se desactivan.
        $tables = ['equipo', 'jugador', 'jornada', 'gol', 'incidencia'];
        $source = new \PDO('sqlite:'.$path, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $source->exec('PRAGMA query_only = ON');
        $source->beginTransaction();
        $data = [];
        foreach ($tables as $table) {
            if ((int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.$table) !== 0) {
                $io->error('El destino contiene registros en '.$table.'. No se sobrescribe ni se mezcla información.');
                return Command::FAILURE;
            }
            $data[$table] = $source->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
        }

        $this->connection->transactional(function (Connection $db) use ($data): void {
            foreach ($data as $table => $rows) {
                foreach ($rows as $row) $db->insert($table, $row);
                $copied = $db->fetchAllAssociative('SELECT * FROM '.$table.' ORDER BY id');
                if (count($copied) !== count($rows)) throw new \RuntimeException('No coincide el total de '.$table);
                foreach ($rows as $index => $row) {
                    foreach ($row as $field => $value) {
                        $actual = $copied[$index][$field];
                        if (($value === null) !== ($actual === null) || (string) $value !== (string) $actual) {
                            throw new \RuntimeException('No coincide el campo '.$table.'.'.$field.'. Se revierte la copia.');
                        }
                    }
                }
            }
        });
        $source->commit();
        $io->table(['Tabla', 'Registros copiados y verificados'], array_map(static fn ($table) => [$table, count($data[$table])], $tables));
        $io->success('Importación completa. Se conservaron identificadores, relaciones, datos y nombres de fotografías. SQLite no se modificó.');

        return Command::SUCCESS;
    }
}
