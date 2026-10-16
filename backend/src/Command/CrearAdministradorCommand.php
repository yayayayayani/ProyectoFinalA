<?php
namespace App\Command;
use App\Entity\Administrador;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
#[AsCommand(name: 'app:crear-admin', description: 'Crea o actualiza una cuenta de administrador o árbitro.')]
final class CrearAdministradorCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('usuario', InputArgument::REQUIRED, 'Nombre de acceso')
            ->addOption('rol', null, InputOption::VALUE_REQUIRED, 'admin o arbitro; las cuentas existentes conservan su rol');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rol = $input->getOption('rol');
        if ($rol !== null && !in_array($rol, ['admin', 'arbitro'], true)) {
            $io->error('El rol debe ser admin o arbitro.');
            return Command::FAILURE;
        }
        $nombre = trim($input->getArgument('usuario'));
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D', $nombre)) { $io->error('Usa entre 3 y 60 letras, números, puntos, guiones o guiones bajos.'); return Command::FAILURE; }
        $admin = $this->em->getRepository(Administrador::class)->findOneBy(['usuario' => $nombre]);
        if ($admin && !$io->confirm('La cuenta existe. ¿Cambiar su contraseña y, si se indicó --rol, su rol?', false)) return Command::FAILURE;
        $clave = $io->askHidden('Contraseña (mínimo 12 caracteres)', static function (?string $value): string {
            if (strlen($value ?? '') < 12) throw new \RuntimeException('Usa al menos 12 caracteres.');
            return $value;
        });
        if ($clave !== $io->askHidden('Repite la contraseña')) { $io->error('Las contraseñas no coinciden.'); return Command::FAILURE; }
        $admin ??= (new Administrador())->setUsuario($nombre);
        if ($rol !== null) $admin->setRol($rol === 'arbitro' ? 'ROLE_ARBITRO' : 'ROLE_ADMIN');
        $admin->setPassword($this->hasher->hashPassword($admin, $clave));
        $this->em->persist($admin);
        $this->em->flush();
        $io->success('Cuenta guardada con rol '.$admin->getRol().'. Puedes iniciar sesión en /login.');
        return Command::SUCCESS;
    }
}
