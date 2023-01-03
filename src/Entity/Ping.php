<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

//use Symfony\Component\Validator\Constraints as Assert;


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
    private int $id;

    /**
     * @ORM\Column(name="is_logged", type="boolean", nullable=false)
     * @OA\Property(
     *     type="bool",
     *     example="false"
     * )
     * @Groups({"create"})
     * @Assert\NotNull()
     * @Assert\Type("bool")
     */
    private bool $isLogged;

    /**
     * @ORM\Column(name="is_located", type="boolean", nullable=false)
     * @OA\Property(
     *     type="bool",
     *     example="true"
     * )
     * @Groups({"create"})
     * @Assert\NotNull()
     * @Assert\Type("bool")
     */
    private bool $isLocated;

    /**
     * @var integer|null
     * @ORM\Column(name="distance_from_trail", type="integer", nullable=true)
     * @OA\Property(
     *     type="int",
     *     example="500"
     * )
     * @Groups({"create"})
     * @Assert\Type("integer")
     */
    private ?int $distanceFromTrail=null;

    /**
     * @ORM\Column(name="is_online", type="boolean", nullable=false)
     * @OA\Property(
     *     type="bool",
     *     example="false"
     * )
     * @Groups({"create"})
     * @Assert\NotNull()
     * @Assert\Type("bool")
     */
    private bool $isOnline;

    /**
     * @var string|null
     * @ORM\Column(name="date", type="string", length=255, nullable=true)
     * @OA\Property(
     *     type="string",
     *     example="2022-11-18 10:52:16"
     * )
     * @Groups({"create"})
     * @Assert\Type("string")
     */
    private ?string $date = null;

    /**
     * @ORM\Column(name="trail", type="integer", nullable=false)
     * @OA\Property(
     *     type="int",
     *     example="25"
     * )
     * @Groups({"create"})
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Type("integer")
     */
    private int $trail;

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

    public function isIsOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): self
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): self
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

    /**
     * @return integer|null
     */
    public function getDistanceFromTrail(): ?int
    {
        return $this->distanceFromTrail;
    }

    /**
     * @param integer|null $distanceFromTrail
     * @return Ping
     */
    public function setDistanceFromTrail(?int $distanceFromTrail): self
    {
        $this->distanceFromTrail = $distanceFromTrail;

        return $this;
    }

}
