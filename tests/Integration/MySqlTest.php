<?php

namespace App\Tests\Integration;

use App\Entity\{Equipo, Jugador, Jornada, Gol, Incidencia};
use App\Service\EstadisticasTorneo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\{EntityManager, ORMSetup};
use PHPUnit\Framework\TestCase;

final class MySqlTest extends TestCase
{
    private ?Connection $db = null;
    private EntityManager $em;

    protected function setUp(): void
    {
        $url = $_ENV['MYSQL_TEST_DATABASE_URL'] ?? $_SERVER['MYSQL_TEST_DATABASE_URL'] ?? null;
        if (!$url) $this->markTestSkipped('Configura MYSQL_TEST_DATABASE_URL para ejecutar la integración real con MySQL.');
        $params = (new DsnParser(['mysql' => 'pdo_mysql']))->parse($url);
        self::assertSame('cantera_torneo_test', $params['dbname'], 'Las pruebas solo pueden usar la base dedicada de pruebas.');
        $this->db = DriverManager::getConnection($params);
        self::assertSame('cantera_torneo_test', $this->db->fetchOne('SELECT DATABASE()'));
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/backend/src/Entity'], true);
        // Usar la misma estrategia de nombres que config/packages/doctrine.yaml.
        $config->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_LOWER));
        $this->em = new EntityManager($this->db, $config);
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db?->isTransactionActive()) $this->db->rollBack();
        $this->db?->close();
    }

    public function testReportsAndUnicodeOnMySql(): void
    {
        $equipo = (new Equipo())->setNombre('Prueba Ñandú');
        $jugador = (new Jugador())->setNombres('María')->setApellidos('Gómez')->setFechaNacimiento(new \DateTimeImmutable('2015-01-01'))->setEquipo($equipo);
        $jornada = (new Jornada())->setNombre('Primera')->setFecha(new \DateTimeImmutable('2026-10-17'));
        $otra = (new Jornada())->setNombre('Segunda')->setFecha(new \DateTimeImmutable('2026-10-24'));
        foreach ([$equipo, $jugador, $jornada, $otra,
            (new Gol())->setJugador($jugador)->setJornada($jornada)->setCantidad(2),
            (new Gol())->setJugador($jugador)->setJornada($otra)->setCantidad(3),
            (new Incidencia())->setJugador($jugador)->setDescripcion('Expulsión de prueba')->setTipoTarjeta('roja')->setFechaIncidencia(new \DateTimeImmutable('2026-10-10'))->setFechaSuspension($jornada->getFecha())] as $entity) $this->em->persist($entity);
        $this->em->flush();
        $service = new EstadisticasTorneo($this->em);
        $ranking = $service->goleadores($equipo);
        self::assertSame('María', $ranking[0]['nombres']);
        self::assertSame('Prueba Ñandú', $ranking[0]['equipo']);
        self::assertSame(5, (int) $ranking[0]['total']);
        self::assertSame(2, (int) $service->goleadores($equipo, $jornada)[0]['total']);
        self::assertTrue($service->nomina($equipo, $jornada)[0]['suspendido']);
        self::assertFalse($service->nomina($equipo, $otra)[0]['suspendido']);
        self::assertCount(1, $service->incidencias($equipo, $jugador));
    }

    public function testForeignKeysAreEnforced(): void
    {
        $this->expectException(\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException::class);
        $this->db->insert('jugador', ['nombres' => 'Prueba', 'apellidos' => 'Temporal', 'fecha_nacimiento' => '2015-01-01', 'equipo_id' => -1]);
    }

    public function testDuplicateGoalsAreRejectedByMySql(): void
    {
        $this->db->insert('equipo', ['nombre' => 'Equipo temporal']);
        $equipoId = $this->db->lastInsertId();
        $this->db->insert('jugador', ['nombres' => 'Prueba', 'apellidos' => 'Temporal', 'fecha_nacimiento' => '2015-01-01', 'equipo_id' => $equipoId]);
        $jugadorId = $this->db->lastInsertId();
        $this->db->insert('jornada', ['nombre' => 'Temporal', 'fecha' => '2026-10-17']);
        $jornadaId = $this->db->lastInsertId();
        $row = ['jugador_id' => $jugadorId, 'jornada_id' => $jornadaId, 'cantidad' => 2];
        $this->db->insert('gol', $row);
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->db->insert('gol', $row);
    }
}
