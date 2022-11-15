<?php

namespace App\Entity;

use App\Repository\PingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PingRepository::class)]
class Ping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $isLogged = null;

    #[ORM\Column]
    private ?bool $isLocated = null;

    #[ORM\Column]
    private ?bool $isCloseToTrail = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $Date = null;

    #[ORM\Column(length: 255)]
    private ?string $trail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isIsLogged(): ?bool
    {
        return $this->isLogged;
    }

    public function setIsLogged(bool $isLogged): self
    {
        $this->isLogged = $isLogged;

        return $this;
    }

    public function isIsLocated(): ?bool
    {
        return $this->isLocated;
    }

    public function setIsLocated(bool $isLocated): self
    {
        $this->isLocated = $isLocated;

        return $this;
    }

    public function isIsCloseToTrail(): ?bool
    {
        return $this->isCloseToTrail;
    }

    public function setIsCloseToTrail(bool $isCloseToTrail): self
    {
        $this->isCloseToTrail = $isCloseToTrail;

        return $this;
    }

    public function isIsOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): self
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(\DateTimeInterface $Date): self
    {
        $this->Date = $Date;

        return $this;
    }

    public function getTrail(): ?string
    {
        return $this->trail;
    }

    public function setTrail(string $trail): self
    {
        $this->trail = $trail;

        return $this;
    }
}
