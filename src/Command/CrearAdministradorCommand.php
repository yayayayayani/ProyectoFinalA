<?php
namespace App\Command;
use App\Entity\Administrador;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
#[AsCommand(name: 'app:crear-admin', description: 'Crea una cuenta administradora o cambia su contraseña.')]
final class CrearAdministradorCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('usuario', InputArgument::REQUIRED, 'Nombre de acceso'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nombre = trim($input->getArgument('usuario'));
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D', $nombre)) { $io->error('Usa entre 3 y 60 letras, números, puntos, guiones o guiones bajos.'); return Command::FAILURE; }
        $admin = $this->em->getRepository(Administrador::class)->findOneBy(['usuario' => $nombre]);
        if ($admin && !$io->confirm('La cuenta existe. ¿Cambiar su contraseña?', false)) return Command::FAILURE;
        $clave = $io->askHidden('Contraseña (mínimo 12 caracteres)', static function (?string $value): string {
            if (strlen($value ?? '') < 12) throw new \RuntimeException('Usa al menos 12 caracteres.');
            return $value;
        });
        if ($clave !== $io->askHidden('Repite la contraseña')) { $io->error('Las contraseñas no coinciden.'); return Command::FAILURE; }
        $admin ??= (new Administrador())->setUsuario($nombre);
        $admin->setPassword($this->hasher->hashPassword($admin, $clave));
        $this->em->persist($admin);
        $this->em->flush();
        $io->success('Cuenta administradora guardada. Puedes iniciar sesión en /login.');
        return Command::SUCCESS;
    }
}
