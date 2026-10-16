<?php

namespace App\Entity;

use App\Repository\EquipoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipoRepository::class)]
class Equipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Ingresa el nombre del equipo.')]
    #[Assert\Length(max: 100)]
    private ?string $nombre = null;

    /**
     * @var Collection<int, Jugador>
     */
    #[ORM\OneToMany(targetEntity: Jugador::class, mappedBy: 'equipo')]
    private Collection $jugadors;

    public function __construct()
    {
        $this->jugadors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * @return Collection<int, Jugador>
     */
    public function getJugadors(): Collection
    {
        return $this->jugadors;
    }

    public function addJugador(Jugador $jugador): static
    {
        if (!$this->jugadors->contains($jugador)) {
            $this->jugadors->add($jugador);
            $jugador->setEquipo($this);
        }

        return $this;
    }

    public function removeJugador(Jugador $jugador): static
    {
        if ($this->jugadors->removeElement($jugador)) {
            // set the owning side to null (unless already changed)
            if ($jugador->getEquipo() === $this) {
                $jugador->setEquipo(null);
            }
        }

        return $this;
    }
    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}
