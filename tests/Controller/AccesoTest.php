<?php
namespace App\Tests\Controller;
use App\Entity\{Administrador, Equipo};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
final class AccesoTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private array $previous;
    protected function setUp(): void
    {
        $this->previous = [$_ENV['DATABASE_URL'] ?? null, $_SERVER['DATABASE_URL'] ?? null];
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
        $this->client = static::createClient();
        $this->client->disableReboot();
        static::getContainer()->get('cache.rate_limiter')->clear();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        self::assertTrue($this->em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $admin = (new Administrador())->setUsuario('organizador');
        $admin->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'ClaveDePrueba2026!'));
        $this->em->persist($admin);
        $this->em->persist((new Equipo())->setNombre('Equipo original'));
        $this->em->flush();
    }
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->previous[0] === null) unset($_ENV['DATABASE_URL']); else $_ENV['DATABASE_URL'] = $this->previous[0];
        if ($this->previous[1] === null) unset($_SERVER['DATABASE_URL']); else $_SERVER['DATABASE_URL'] = $this->previous[1];
    }
    public function testPdfDownloadsRespectReportPermissions(): void
    {
        foreach (['arbitro', 'incidencias'] as $tipo) {
            $this->client->request('GET', '/reportes/'.$tipo, ['formato' => 'pdf']);
            self::assertResponseRedirects('/login');
        }
        $this->client->request('GET', '/reportes/goleadores', ['formato' => 'pdf']);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', $this->client->getResponse()->getContent());
    }

    public function testUserModuleRequiresAdministrator(): void
    {
        foreach (['GET', 'POST'] as $method) {
            $this->client->request($method, '/usuarios/new');
            self::assertResponseRedirects('/login');
        }
        $arbitro = (new Administrador())->setUsuario('lector')->setRol('ROLE_ARBITRO')->setPassword('unused');
        $this->em->persist($arbitro);
        $this->em->flush();
        $this->client->loginUser($arbitro);
        foreach (['/usuarios', '/usuarios/new'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403);
        }
        $this->client->request('POST', '/usuarios/new');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdministratorCanRegisterBothRolesWithHashedPasswords(): void
    {
        $this->client->loginUser($this->em->getRepository(Administrador::class)->findOneBy(['usuario' => 'organizador']));
        foreach (['ROLE_ARBITRO', 'ROLE_ADMIN'] as $rol) {
            $page = $this->client->request('GET', '/usuarios/new');
            $this->client->submit($page->selectButton('Registrar usuario')->form([
                'usuario[usuario]' => 'nuevo_'.$rol,
                'usuario[rol]' => $rol,
                'usuario[password][first]' => 'ClaveNueva2026!',
                'usuario[password][second]' => 'ClaveNueva2026!',
            ]));
            self::assertResponseRedirects('/usuarios', 303);
            $usuario = $this->em->getRepository(Administrador::class)->findOneBy(['usuario' => 'nuevo_'.$rol]);
            self::assertSame($rol, $usuario->getRol());
            self::assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($usuario, 'ClaveNueva2026!'));
            $this->client->followRedirect();
            self::assertSelectorTextContains('main', 'nuevo_'.$rol);
            self::assertStringNotContainsString($usuario->getPassword(), $this->client->getResponse()->getContent());
        }
    }

    public function testUserRegistrationRejectsInvalidDataAndDuplicateNames(): void
    {
        $this->client->loginUser($this->em->getRepository(Administrador::class)->findOneBy(['usuario' => 'organizador']));
        $valid = ['usuario' => 'nuevo', 'rol' => 'ROLE_ARBITRO', 'password' => ['first' => 'ClaveNueva2026!', 'second' => 'ClaveNueva2026!']];
        foreach ([
            ['usuario' => 'organizador'],
            ['usuario' => 'nombre inválido'],
            ['rol' => 'ROLE_SUPER_ADMIN'],
            ['password' => ['first' => 'corta', 'second' => 'corta']],
            ['password' => ['first' => 'ClaveNueva2026!', 'second' => 'OtraClave2026!']],
            ['_token' => 'incorrecto'],
        ] as $invalid) {
            $page = $this->client->request('GET', '/usuarios/new');
            $token = $page->filter('input[name="usuario[_token]"]')->attr('value');
            $this->client->request('POST', '/usuarios/new', ['usuario' => array_replace($valid, ['_token' => $token], $invalid)]);
            self::assertResponseStatusCodeSame(422);
            self::assertSame(1, $this->em->getRepository(Administrador::class)->count([]));
        }
    }

    public function testVisitorCanReadButCannotWriteOrSeeEditButtons(): void
    {
        foreach (['/', '/equipo', '/jornada', '/reportes/goleadores'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful();
            self::assertSelectorNotExists('a[href$="/new"], a[href$="/edit"]');
            self::assertSelectorExists('a[href="/login"]');
            self::assertSelectorNotExists('a[href="/jugador"], a[href="/incidencia"], a[href="/reportes/arbitro"], a[href="/reportes/incidencias"]');
        }
        foreach (['/jugador', '/jugador/1', '/jugador/1/fotografia', '/gol', '/incidencia', '/reportes/arbitro', '/reportes/incidencias'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseRedirects('/login');
        }
        foreach (['equipo','jugador','jornada','gol','incidencia'] as $modulo) {
            foreach (['/'.$modulo.'/new','/'.$modulo.'/1/edit'] as $url) {
                $this->client->request('GET', $url);
                self::assertResponseRedirects('/login');
                $this->client->request('POST', $url, []);
                self::assertResponseRedirects('/login');
            }
            $this->client->request('POST', '/'.$modulo.'/1'.(in_array($modulo,['equipo','jugador'])?'':'/delete'));
            self::assertResponseRedirects('/login');
        }
        self::assertSame(1,$this->em->getRepository(Equipo::class)->count([]));
    }
    public function testArbitroCanConsultButCannotMutateAnyModule(): void
    {
        $arbitro = (new Administrador())->setUsuario('arbitro')->setRol('ROLE_ARBITRO');
        $arbitro->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($arbitro, 'ClaveArbitro2026!'));
        $this->em->persist($arbitro);
        $this->em->flush();
        $this->em->clear();
        $page = $this->client->request('GET', '/login');
        $this->client->submit($page->selectButton('Iniciar sesión')->form(['_username' => 'arbitro', '_password' => 'ClaveArbitro2026!']));
        self::assertResponseRedirects('/');
        foreach (['/', '/equipo', '/jugador', '/jornada', '/gol', '/incidencia', '/reportes/arbitro', '/reportes/incidencias', '/reportes/goleadores'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
            self::assertSelectorNotExists('a[href$="/new"], a[href$="/edit"]');
            self::assertSelectorTextContains('.session-label', 'Árbitro');
            self::assertSelectorExists('form[action="/logout"]');
        }
        foreach (['equipo', 'jugador', 'jornada', 'gol', 'incidencia'] as $modulo) {
            foreach (['GET', 'POST'] as $method) {
                foreach (['/'.$modulo.'/new', '/'.$modulo.'/1/edit'] as $url) {
                    $this->client->request($method, $url);
                    self::assertResponseStatusCodeSame(403);
                }
            }
            $this->client->request('POST', '/'.$modulo.'/1'.(in_array($modulo, ['equipo', 'jugador']) ? '' : '/delete'));
            self::assertResponseStatusCodeSame(403);
        }
        self::assertSame(1, $this->em->getRepository(Equipo::class)->count([]));
        $page = $this->client->request('GET', '/');
        $this->client->submit($page->selectButton('Cerrar sesión')->form());
        self::assertResponseRedirects('/');
        $this->client->request('GET', '/reportes/arbitro');
        self::assertResponseRedirects('/login');
    }

    public function testVisitorTeamRosterDoesNotExposeBirthDateOrPrivateLinks(): void
    {
        $equipo = $this->em->getRepository(Equipo::class)->findOneBy([]);
        $jugador = (new \App\Entity\Jugador())->setNombres('María')->setApellidos('Gómez')
            ->setFechaNacimiento(new \DateTimeImmutable('2015-01-01'))->setEquipo($equipo);
        $this->em->persist($jugador);
        $this->em->flush();
        $equipoId = $equipo->getId();
        $this->em->clear();
        $this->client->request('GET', '/equipo/'.$equipoId);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('tbody', 'María Gómez');
        self::assertStringNotContainsString('01/01/2015', $this->client->getResponse()->getContent());
        self::assertSelectorNotExists('a[href^="/jugador/"], img[src*="/fotografia"]');
    }
    public function testLoginLogoutAndWrongPassword(): void
    {
        $page = $this->client->request('GET','/login');
        $this->client->submit($page->selectButton('Iniciar sesión')->form(['_username'=>'organizador','_password'=>'incorrecta']));
        self::assertResponseRedirects('/login');
        $page = $this->client->followRedirect();
        self::assertSelectorExists('[role="alert"]');
        $this->client->submit($page->selectButton('Iniciar sesión')->form(['_username'=>'organizador','_password'=>'ClaveDePrueba2026!']));
        self::assertResponseRedirects('/');
        $page = $this->client->followRedirect();
        self::assertSelectorExists('a[href="/jugador/new"]');
        $this->client->request('GET','/equipo/new');
        self::assertResponseIsSuccessful();
        $page=$this->client->request('GET','/');
        $this->client->submit($page->selectButton('Cerrar sesión')->form());
        self::assertResponseRedirects('/');
        $this->client->request('GET','/equipo/new');
        self::assertResponseRedirects('/login');
    }
    public function testLoginAndLogoutBehindHttpsProxyUseSessionCsrfTokens(): void
    {
        $server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'lacantera.lol', 'HTTP_X_FORWARDED_PROTO' => 'https'];
        $page = $this->client->request('GET', 'https://lacantera.lol/login', [], [], $server);
        $token = $page->filter('input[name="_csrf_token"]')->attr('value');
        self::assertNotSame('csrf-token', $token);
        // No depende de Origin, Referer ni de una cookie generada con JavaScript.
        $this->client->request('POST', 'https://lacantera.lol/login', [
            '_username' => 'organizador', '_password' => 'ClaveDePrueba2026!', '_csrf_token' => $token,
        ], [], $server);
        self::assertResponseRedirects('https://lacantera.lol/');
        $page = $this->client->request('GET', 'https://lacantera.lol/', [], [], $server);
        self::assertSelectorExists('form[action="/logout"]');
        $logoutToken = $page->filter('form[action="/logout"] input[name="_csrf_token"]')->attr('value');
        $this->client->request('POST', 'https://lacantera.lol/logout', ['_csrf_token' => $logoutToken], [], $server);
        self::assertResponseRedirects('https://lacantera.lol/');
        $this->client->request('GET', 'https://lacantera.lol/usuarios', [], [], $server);
        self::assertResponseRedirects('https://lacantera.lol/login');
    }

    public function testInvalidCsrfCannotAuthenticate(): void
    {
        $this->client->request('POST','/login',['_username'=>'organizador','_password'=>'ClaveDePrueba2026!','_csrf_token'=>'incorrecto']);
        self::assertResponseRedirects('/login');
        $this->client->request('GET','/equipo/new');
        self::assertResponseRedirects('/login');
    }

    public function testRepeatedFailuresTemporarilyBlockEvenTheCorrectPassword(): void
    {
        for ($i = 0; $i < 6; ++$i) {
            $page = $this->client->request('GET', '/login');
            $this->client->submit($page->selectButton('Iniciar sesión')->form([
                '_username' => 'organizador', '_password' => 'incorrecta',
            ]));
            self::assertResponseRedirects('/login');
        }
        $page = $this->client->request('GET', '/login');
        $this->client->submit($page->selectButton('Iniciar sesión')->form([
            '_username' => 'organizador', '_password' => 'ClaveDePrueba2026!',
        ]));
        self::assertResponseRedirects('/login');
        $this->client->request('GET', '/equipo/new');
        self::assertResponseRedirects('/login');
    }

    public function testSecurityHeadersAndSecureCookieBehindLocalProxy(): void
    {
        $this->client->request('GET', '/login', [], [], [
            'REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        self::assertResponseHeaderSame('X-Frame-Options', 'DENY');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        self::assertStringContainsString("frame-ancestors 'none'", $this->client->getResponse()->headers->get('Content-Security-Policy'));
        self::assertTrue($this->client->getResponse()->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($this->client->getRequest()->isSecure());
        $this->client->request('GET', '/login', [], [], [
            'REMOTE_ADDR' => '192.0.2.123', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        self::assertFalse($this->client->getRequest()->isSecure(), 'No confiar en cabeceras de proxies desconocidos.');
    }
}
