<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="App\Repository\PingRepository")
 *
 */
class Ping
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="isLogged", type="boolean", nullable=false)
     */
    private $isLogged = null;

    /**
     * @ORM\Column(name="isLocated", type="boolean", nullable=false)
     */
    private  $isLocated = null;

    /**
     * @ORM\Column(name="isCloseToTrail", type="boolean", nullable=false)
     */
    private $isCloseToTrail = null;

    /**
     * @ORM\Column(name="isOnline", type="boolean", nullable=false)
     */
    private $isOnline = null;

    /**
     * @ORM\Column(name="date", type="string", length=255, nullable=true)
     */
    private $date = null;

    /**
     * @ORM\Column(name="trail", type="integer", nullable=false)
     */
    private $trail = null;

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

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTrail(): ?int
    {
        return $this->trail;
    }

    public function setTrail(int $trail): self
    {
        $this->trail = $trail;

        return $this;
    }
}
