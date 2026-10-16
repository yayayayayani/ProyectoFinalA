<?php

namespace App\Tests\Controller;

use App\Entity\{Equipo, Jugador, Jornada, Gol, Incidencia};
use App\Service\EstadisticasTorneo;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TorneoTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private array $previous;
    private int $jugadorId;
    private int $equipoId;
    private int $jornadaId;

    protected function setUp(): void
    {
        $this->previous = [$_ENV['DATABASE_URL'] ?? null, $_SERVER['DATABASE_URL'] ?? null];
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        self::assertTrue($this->em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $admin = (new \App\Entity\Administrador())->setUsuario('admin_test')->setPassword('unused');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);

        $equipo = (new Equipo())->setNombre('Equipo de prueba');
        $jugador = (new Jugador())->setNombres('María')->setApellidos('Gómez')->setFechaNacimiento(new \DateTimeImmutable('2015-01-01'))->setEquipo($equipo);
        $jornada = (new Jornada())->setNombre('Jornada inicial')->setFecha(new \DateTimeImmutable('2026-10-17'));
        foreach ([$equipo, $jugador, $jornada] as $entity) $this->em->persist($entity);
        $this->em->flush();
        $this->jugadorId = $jugador->getId();
        $this->equipoId = $equipo->getId();
        $this->jornadaId = $jornada->getId();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->previous[0] === null) unset($_ENV['DATABASE_URL']);
        else $_ENV['DATABASE_URL'] = $this->previous[0];
        if ($this->previous[1] === null) unset($_SERVER['DATABASE_URL']);
        else $_SERVER['DATABASE_URL'] = $this->previous[1];
    }

    public function testAllScreensRenderAndInvalidFiltersAreHandled(): void
    {
        foreach (['/', '/equipo', '/equipo/new', '/equipo/'.$this->equipoId, '/equipo/'.$this->equipoId.'/edit',
            '/jugador', '/jugador/new', '/jugador/'.$this->jugadorId, '/jornada', '/jornada/new', '/jornada/'.$this->jornadaId.'/edit',
            '/gol', '/gol/new', '/incidencia', '/incidencia/new', '/reportes/arbitro', '/reportes/goleadores', '/reportes/incidencias'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
            self::assertSelectorExists('html[lang="es"]');
        }
        $this->client->request('GET', '/reportes/arbitro', ['form' => ['equipo' => '', 'jornada' => '9999']]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testJornadaCreateEditDeleteAndCsrf(): void
    {
        $crawler = $this->client->request('GET', '/jornada/new');
        $this->client->submit($crawler->selectButton('Guardar jornada')->form(['jornada[nombre]' => 'Jornada de prueba', 'jornada[fecha]' => '2026-10-24']));
        self::assertResponseRedirects('/jornada', 303);
        $entity = $this->em->getRepository(Jornada::class)->findOneBy(['nombre' => 'Jornada de prueba']);
        $id = $entity->getId();
        $crawler = $this->client->request('GET', '/jornada/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Guardar jornada')->form(['jornada[nombre]' => 'Jornada corregida']));
        self::assertResponseRedirects('/jornada', 303);
        $this->em->clear();
        self::assertSame('Jornada corregida', $this->em->find(Jornada::class, $id)->getNombre());
        $this->client->request('POST', '/jornada/'.$id.'/delete', ['_token' => 'incorrecto']);
        self::assertResponseStatusCodeSame(403);
        $crawler = $this->client->request('GET', '/jornada/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Eliminar registro')->form());
        self::assertResponseRedirects('/jornada', 303);
        self::assertNull($this->em->find(Jornada::class, $id));
    }

    public function testGoalsValidationDuplicateAndDeletionProtection(): void
    {
        $values = ['gol[jugador]' => $this->jugadorId, 'gol[jornada]' => $this->jornadaId, 'gol[cantidad]' => 3];
        $crawler = $this->client->request('GET', '/gol/new');
        $this->client->submit($crawler->selectButton('Guardar goles')->form($values));
        self::assertResponseRedirects('/gol', 303);
        self::assertSame(1, $this->em->getRepository(Gol::class)->count([]));
        $crawler = $this->client->request('GET', '/gol/new');
        $this->client->submit($crawler->selectButton('Guardar goles')->form($values));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'Ya existe un registro');
        $crawler = $this->client->request('GET', '/jornada/'.$this->jornadaId.'/edit');
        $this->client->submit($crawler->selectButton('Eliminar registro')->form());
        self::assertResponseRedirects('/jornada', 303);
        self::assertNotNull($this->em->find(Jornada::class, $this->jornadaId));
        $crawler = $this->client->request('GET', '/jugador/'.$this->jugadorId.'/edit');
        $this->client->submit($crawler->selectButton('Eliminar jugador')->form());
        self::assertResponseRedirects('/jugador/'.$this->jugadorId, 303);
        self::assertNotNull($this->em->find(Jugador::class, $this->jugadorId));
        $crawler = $this->client->request('GET', '/equipo/'.$this->equipoId);
        $this->client->submit($crawler->selectButton('Eliminar equipo')->form());
        self::assertResponseRedirects('/equipo/'.$this->equipoId, 303);
        self::assertNotNull($this->em->find(Equipo::class, $this->equipoId));
    }

    public function testRedCardRequiresSuspensionAndYellowRejectsIt(): void
    {
        $values = ['incidencia[jugador]' => $this->jugadorId, 'incidencia[descripcion]' => 'Falta de prueba',
            'incidencia[tipoTarjeta]' => 'roja', 'incidencia[fechaIncidencia]' => '2026-10-10', 'incidencia[fechaSuspension]' => ''];
        $crawler = $this->client->request('GET', '/incidencia/new');
        $this->client->submit($crawler->selectButton('Guardar incidencia')->form($values));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'Una tarjeta roja requiere fecha de suspensión.');
        $values['incidencia[fechaSuspension]'] = '2026-10-17';
        $crawler = $this->client->request('GET', '/incidencia/new');
        $this->client->submit($crawler->selectButton('Guardar incidencia')->form($values));
        self::assertResponseRedirects('/incidencia', 303);
        $values['incidencia[tipoTarjeta]'] = 'amarilla';
        $crawler = $this->client->request('GET', '/incidencia/new');
        $this->client->submit($crawler->selectButton('Guardar incidencia')->form($values));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'La suspensión solo aplica a tarjetas rojas.');
    }

    public function testReportsAggregateFilterAndMatchExactSuspensionDate(): void
    {
        $equipo = $this->em->find(Equipo::class, $this->equipoId);
        $jugador = $this->em->find(Jugador::class, $this->jugadorId);
        $jornada = $this->em->find(Jornada::class, $this->jornadaId);
        $otherEquipo = (new Equipo())->setNombre('Otro equipo');
        $otherJugador = (new Jugador())->setNombres('Juan')->setApellidos('Pérez')->setEquipo($otherEquipo)->setFechaNacimiento(new \DateTimeImmutable('2014-01-01'));
        $otherJornada = (new Jornada())->setNombre('Segunda jornada')->setFecha(new \DateTimeImmutable('2026-10-24'));
        foreach ([$otherEquipo, $otherJugador, $otherJornada,
            (new Gol())->setJugador($jugador)->setJornada($jornada)->setCantidad(2),
            (new Gol())->setJugador($jugador)->setJornada($otherJornada)->setCantidad(3),
            (new Gol())->setJugador($otherJugador)->setJornada($jornada)->setCantidad(4),
            (new Incidencia())->setJugador($jugador)->setDescripcion('Expulsión')->setTipoTarjeta('roja')->setFechaIncidencia(new \DateTimeImmutable('2026-10-10'))->setFechaSuspension($jornada->getFecha())] as $e) $this->em->persist($e);
        $this->em->flush();
        $service = static::getContainer()->get(EstadisticasTorneo::class);
        $ranking = $service->goleadores();
        self::assertSame($this->jugadorId, $ranking[0]['id']);
        self::assertSame(5, (int) $ranking[0]['total']);
        self::assertCount(1, $service->goleadores($equipo));
        self::assertSame(2, (int) $service->goleadores($equipo, $jornada)[0]['total']);
        self::assertTrue($service->nomina($equipo, $jornada)[0]['suspendido']);
        self::assertFalse($service->nomina($equipo, $otherJornada)[0]['suspendido']);
        self::assertCount(1, $service->incidencias($equipo, $jugador));
        self::assertCount(0, $service->incidencias($otherEquipo));
        $this->client->request('GET', '/reportes/arbitro', ['form' => ['equipo' => $equipo->getId(), 'jornada' => $jornada->getId()]]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('tbody', 'Suspendido');
        self::assertSelectorTextNotContains('tbody', 'Juan');
        $this->client->request('GET', '/reportes/incidencias', ['form' => ['equipo' => $equipo->getId(), 'jugador' => $otherJugador->getId()]]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'El jugador no pertenece al equipo seleccionado.');
    }

    public function testInvalidNamesDatesAndGoalQuantities(): void
    {
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $jugador = $this->em->find(Jugador::class, $this->jugadorId);
        $jugador->setNombres('')->setFechaNacimiento(new \DateTimeImmutable('+1 year'));
        self::assertCount(2, $validator->validate($jugador));
        $gol = (new Gol())->setJugador($jugador)->setJornada($this->em->find(Jornada::class, $this->jornadaId))->setCantidad(-1);
        self::assertGreaterThan(0, $validator->validate($gol)->count());
        $i = (new Incidencia())->setJugador($jugador)->setDescripcion('Prueba')->setTipoTarjeta('roja')
            ->setFechaIncidencia(new \DateTimeImmutable('2026-10-17'))->setFechaSuspension(new \DateTimeImmutable('2026-10-10'));
        self::assertGreaterThan(0, $validator->validate($i)->count());
    }

    public function testMissingBirthDateShowsValidationInsteadOfSaving(): void
    {
        $crawler = $this->client->request('GET', '/jugador/new');
        $this->client->submit($crawler->selectButton('Guardar jugador')->form([
            'jugador[nombres]' => 'Nuevo', 'jugador[apellidos]' => 'Jugador',
            'jugador[fechaNacimiento]' => '', 'jugador[equipo]' => $this->equipoId,
        ]));
        self::assertResponseStatusCodeSame(422);
        self::assertSame(1, $this->em->getRepository(Jugador::class)->count([]));
    }
}
