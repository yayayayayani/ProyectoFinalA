<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
#[ORM\Entity]
#[ORM\UniqueConstraint(name: "UNIQ_ADMIN_USUARIO", columns: ["usuario"])]
class Administrador implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 180)]
    private string $usuario = '';
    #[ORM\Column(length: 255)]
    private string $password = '';
    public function getId(): ?int { return $this->id; }
    public function getUserIdentifier(): string { return $this->usuario; }
    public function setUsuario(string $usuario): self { $this->usuario = $usuario; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getRoles(): array { return ['ROLE_ADMIN']; }
    public function eraseCredentials(): void {}
}
