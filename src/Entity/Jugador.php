<?php

namespace App\Entity;

use App\Repository\JugadorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JugadorRepository::class)]
class Jugador
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Ingresa los nombres del jugador.')]
    #[Assert\Length(max: 100)]
    private ?string $nombres = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Ingresa los apellidos del jugador.')]
    #[Assert\Length(max: 100)]
    private ?string $apellidos = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual('today', message: 'La fecha de nacimiento no puede ser futura.')]
    private ?\DateTimeImmutable $fechaNacimiento = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fotografia = null;

    #[ORM\ManyToOne(inversedBy: 'jugadors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Selecciona un equipo.')]
    private ?Equipo $equipo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombreCompleto(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function __toString(): string
    {
        return $this->getNombreCompleto().' · '.($this->equipo?->getNombre() ?? 'Sin equipo');
    }

    public function getNombres(): ?string
    {
        return $this->nombres;
    }

    public function setNombres(string $nombres): static
    {
        $this->nombres = $nombres;

        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeImmutable $fechaNacimiento): static
    {
        $this->fechaNacimiento = $fechaNacimiento;

        return $this;
    }

    public function getFotografia(): ?string
    {
        return $this->fotografia;
    }

    public function setFotografia(?string $fotografia): static
    {
        $this->fotografia = $fotografia;

        return $this;
    }

    public function getEquipo(): ?Equipo
    {
        return $this->equipo;
    }

    public function setEquipo(?Equipo $equipo): static
    {
        $this->equipo = $equipo;

        return $this;
    }
}
