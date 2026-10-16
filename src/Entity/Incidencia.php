<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
class Incidencia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Jugador $jugador = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    private ?string $descripcion = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['roja', 'amarilla'])]
    private ?string $tipoTarjeta = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $fechaIncidencia = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechaSuspension = null;

    public function getId(): ?int { return $this->id; }

    public function getJugador(): ?Jugador
    {
        return $this->jugador;
    }

    public function setJugador(?Jugador $jugador): static
    {
        $this->jugador = $jugador;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getTipoTarjeta(): ?string
    {
        return $this->tipoTarjeta;
    }

    public function setTipoTarjeta(?string $tipoTarjeta): static
    {
        $this->tipoTarjeta = $tipoTarjeta;

        return $this;
    }

    public function getFechaIncidencia(): ?\DateTimeImmutable
    {
        return $this->fechaIncidencia;
    }

    public function setFechaIncidencia(?\DateTimeImmutable $fechaIncidencia): static
    {
        $this->fechaIncidencia = $fechaIncidencia;

        return $this;
    }

    public function getFechaSuspension(): ?\DateTimeImmutable
    {
        return $this->fechaSuspension;
    }

    public function setFechaSuspension(?\DateTimeImmutable $fechaSuspension): static
    {
        $this->fechaSuspension = $fechaSuspension;

        return $this;
    }

    #[Assert\Callback]
    public function validateSuspension(ExecutionContextInterface $context): void
    {
        if ($this->tipoTarjeta === 'roja' && $this->fechaSuspension === null) {
            $context->buildViolation('Una tarjeta roja requiere fecha de suspensión.')->atPath('fechaSuspension')->addViolation();
        }
        if ($this->tipoTarjeta === 'amarilla' && $this->fechaSuspension !== null) {
            $context->buildViolation('La suspensión solo aplica a tarjetas rojas.')->atPath('fechaSuspension')->addViolation();
        }
        if ($this->fechaSuspension !== null && $this->fechaIncidencia !== null && $this->fechaSuspension < $this->fechaIncidencia) {
            $context->buildViolation('La suspensión no puede ser anterior a la incidencia.')->atPath('fechaSuspension')->addViolation();
        }
    }
}
