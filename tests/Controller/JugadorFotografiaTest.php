<?php

namespace App\Tests\Controller;

use App\Entity\Equipo;
use App\Entity\Jugador;
use App\Service\FotografiaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class JugadorFotografiaTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $directory;
    private array $previousDatabaseUrl;
    private int $equipoId;

    protected function setUp(): void
    {
        $this->previousDatabaseUrl = [$_ENV['DATABASE_URL'] ?? null, $_SERVER['DATABASE_URL'] ?? null];
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        // Nunca crear ni borrar tablas si la conexión no es exclusivamente en memoria.
        self::assertTrue($this->em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $admin = (new \App\Entity\Administrador())->setUsuario('admin_test')->setPassword('unused');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);

        $equipo = (new Equipo())->setNombre('Los Tigres');
        $this->em->persist($equipo);
        $this->em->flush();
        $this->equipoId = $equipo->getId();
        $this->directory = sys_get_temp_dir().'/torneo-fotos-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory);
        static::getContainer()->set(FotografiaUploader::class, new FotografiaUploader($this->directory.'/uploads'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (isset($this->directory)) {
            (new Filesystem())->remove($this->directory);
        }
        // Restaurar el entorno que existía antes de cada prueba.
        if ($this->previousDatabaseUrl[0] === null) {
            unset($_ENV['DATABASE_URL']);
        } else {
            $_ENV['DATABASE_URL'] = $this->previousDatabaseUrl[0];
        }
        if ($this->previousDatabaseUrl[1] === null) {
            unset($_SERVER['DATABASE_URL']);
        } else {
            $_SERVER['DATABASE_URL'] = $this->previousDatabaseUrl[1];
        }
    }

    public function testInvalidPlayerDataCannotBeCreatedOrSavedOnEdit(): void
    {
        $this->createJugador();
        self::assertResponseRedirects('/jugador', 303);
        $id = $this->em->getRepository(Jugador::class)->findOneBy(['nombres' => 'Ana'])->getId();
        foreach (['/jugador/new', '/jugador/'.$id.'/edit'] as $url) {
            foreach ([
                ['nombres' => '1221212321'],
                ['apellidos' => '12312312321'],
                ['nombres' => 'Ana123'],
                ['apellidos' => '<script>'],
                ['nombres' => '   '],
                ['apellidos' => str_repeat('a', 101)],
                ['fechaNacimiento' => (new \DateTimeImmutable('today'))->format('Y-m-d')],
                ['fechaNacimiento' => (new \DateTimeImmutable('tomorrow'))->format('Y-m-d')],
                ['fechaNacimiento' => (new \DateTimeImmutable('today -6 years +1 day'))->format('Y-m-d')],
                ['fechaNacimiento' => '2020-02-30'],
                ['equipo' => '999999'],
            ] as $invalid) {
                $crawler = $this->client->request('GET', $url);
                $form = $crawler->filter('form[name="jugador"]')->form();
                $values = $form->getPhpValues();
                $values['jugador'] = array_replace($values['jugador'], [
                    'nombres' => 'Ana', 'apellidos' => 'López',
                    'fechaNacimiento' => '2016-05-10', 'equipo' => $this->equipoId,
                ], $invalid);
                // POST directo: comprueba el servidor sin depender de validación HTML.
                $this->client->request('POST', $url, $values);
                self::assertFalse($this->client->getResponse()->isRedirect(), $url.' '.json_encode($invalid).' redirect: '.$this->client->getResponse()->headers->get('Location'));
                self::assertSelectorExists('form li');
                $this->em->clear();
                self::assertSame(1, $this->em->getRepository(Jugador::class)->count([]));
                self::assertSame('Ana', $this->em->find(Jugador::class, $id)->getNombres());
                self::assertSame('López', $this->em->find(Jugador::class, $id)->getApellidos());
                self::assertSame('2016-05-10', $this->em->find(Jugador::class, $id)->getFechaNacimiento()->format('Y-m-d'));
            }
        }
        $crawler = $this->client->request('GET', '/jugador/'.$id.'/edit');
        $form = $crawler->filter('form[name="jugador"]')->form([
            'jugador[nombres]' => 'María-José',
            'jugador[apellidos]' => 'O’Neill de la Peña',
            'jugador[fechaNacimiento]' => (new \DateTimeImmutable('today -6 years'))->format('Y-m-d'),
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/jugador', 303);
    }

    private function imageFile(string $name = 'foto.png'): UploadedFile
    {
        $path = $this->directory.'/'.bin2hex(random_bytes(4)).'.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function createJugador(?UploadedFile $file = null): void
    {
        $crawler = $this->client->request('GET', '/jugador/new');
        self::assertSelectorTextContains('select[name="jugador[equipo]"]', 'Los Tigres');
        self::assertSelectorExists('form[enctype="multipart/form-data"]');
        $form = $crawler->selectButton('Guardar jugador')->form([
            'jugador[nombres]' => 'Ana',
            'jugador[apellidos]' => 'López',
            'jugador[fechaNacimiento]' => '2016-05-10',
            'jugador[equipo]' => $this->equipoId,
        ]);
        $files = $file ? ['jugador' => ['fotografiaArchivo' => $file]] : [];
        $this->client->request('POST', '/jugador/new', $form->getPhpValues(), $files);
    }

    public function testUploadDisplayAndEditPreservePhotographs(): void
    {
        $this->createJugador($this->imageFile());
        self::assertResponseRedirects('/jugador', 303);
        $jugador = $this->em->getRepository(Jugador::class)->findOneBy(['nombres' => 'Ana']);
        $id = $jugador->getId();
        $filename = $jugador->getFotografia();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $filename);
        self::assertFileExists($this->directory.'/uploads/'.$filename);
        $this->client->followRedirect();
        self::assertSelectorTextContains('tbody', 'Los Tigres');
        self::assertSelectorExists('img[src="/jugador/'.$id.'/fotografia"]');
        $this->client->request('GET', '/jugador/'.$id.'/fotografia');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/png');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');

        $crawler = $this->client->request('GET', '/jugador/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['jugador[nombres]' => 'Ana María']));
        self::assertResponseRedirects('/jugador', 303);
        $this->em->clear();
        self::assertSame($filename, $this->em->find(Jugador::class, $id)->getFotografia());

        $crawler = $this->client->request('GET', '/jugador/'.$id.'/edit');
        $form = $crawler->selectButton('Guardar cambios')->form();
        $this->client->request('POST', '/jugador/'.$id.'/edit', $form->getPhpValues(), ['jugador' => ['fotografiaArchivo' => $this->imageFile()]]);
        self::assertResponseRedirects('/jugador', 303);
        $this->em->clear();
        self::assertNotSame($filename, $this->em->find(Jugador::class, $id)->getFotografia());
        self::assertFileExists($this->directory.'/uploads/'.$filename);
    }

    public function testPhotoIsOptionalAndLegacyValueIsPreserved(): void
    {
        $this->createJugador();
        self::assertResponseRedirects('/jugador', 303);
        $jugador = $this->em->getRepository(Jugador::class)->findOneBy([]);
        self::assertNull($jugador->getFotografia());
        $jugador->setFotografia('../../archivo-antiguo.png');
        $this->em->flush();
        $id = $jugador->getId();
        $crawler = $this->client->request('GET', '/jugador/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form());
        self::assertResponseRedirects('/jugador', 303);
        $this->em->clear();
        self::assertSame('../../archivo-antiguo.png', $this->em->find(Jugador::class, $id)->getFotografia());
        $this->client->request('GET', '/jugador/'.$id.'/fotografia');
        self::assertResponseStatusCodeSame(404);
    }

    public function testRejectsExecutableDisguisedAsImage(): void
    {
        $path = $this->directory.'/fake.png';
        file_put_contents($path, '<?php echo "not an image";');
        $this->createJugador(new UploadedFile($path, 'foto.png', 'image/png', null, true));
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em->getRepository(Jugador::class)->count([]));
        self::assertDirectoryDoesNotExist($this->directory.'/uploads');
    }

    public function testRejectsOversizedImage(): void
    {
        $file = $this->imageFile();
        file_put_contents($file->getPathname(), str_repeat('x', 2000001), FILE_APPEND);
        $this->createJugador($file);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'La fotografía no debe superar 2 MB.');
        self::assertSame(0, $this->em->getRepository(Jugador::class)->count([]));
    }

    public function testWriteFailureDoesNotSaveJugador(): void
    {
        $blocked = $this->directory.'/uploads';
        file_put_contents($blocked, 'existing file');
        $this->createJugador($this->imageFile());
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'No se pudo guardar la fotografía.');
        self::assertSame(0, $this->em->getRepository(Jugador::class)->count([]));
        self::assertSame('existing file', file_get_contents($blocked));
    }

    public function testRejectsSvg(): void
    {
        $path = $this->directory.'/foto.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><script>alert(1)</script></svg>');
        $this->createJugador(new UploadedFile($path, 'foto.svg', 'image/svg+xml', null, true));
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em->getRepository(Jugador::class)->count([]));
    }

    public function testInvalidReplacementKeepsOriginalPhoto(): void
    {
        $this->createJugador($this->imageFile());
        $jugador = $this->em->getRepository(Jugador::class)->findOneBy([]);
        $id = $jugador->getId();
        $original = $jugador->getFotografia();
        $crawler = $this->client->request('GET', '/jugador/'.$id.'/edit');
        $form = $crawler->selectButton('Guardar cambios')->form();
        $this->client->request('POST', '/jugador/'.$id.'/edit', $form->getPhpValues(), [
            'jugador' => ['fotografiaArchivo' => $this->imageFile('script.php')],
        ]);
        self::assertResponseStatusCodeSame(422);
        $this->em->clear();
        self::assertSame($original, $this->em->find(Jugador::class, $id)->getFotografia());
        self::assertFileExists($this->directory.'/uploads/'.$original);
        self::assertCount(1, glob($this->directory.'/uploads/*'));
    }
}
