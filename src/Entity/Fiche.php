<?php

namespace App\Entity;

use App\Repository\FicheRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FicheRepository::class)
 */
class Fiche
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tag;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $referentiel;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_modification;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $usages;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $ecologie;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $sources;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $proprietaire;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $derniere_version;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getNt(): ?string
    {
        return $this->nt;
    }

    public function setNt(?string $nt): self
    {
        $this->nt = $nt;

        return $this;
    }

    public function getReferentiel(): ?string
    {
        return $this->referentiel;
    }

    public function setReferentiel(?string $referentiel): self
    {
        $this->referentiel = $referentiel;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?\DateTimeInterface $date_modification): self
    {
        $this->date_modification = $date_modification;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUsages(): ?string
    {
        return $this->usages;
    }

    public function setUsages(?string $usages): self
    {
        $this->usages = $usages;

        return $this;
    }

    public function getEcologie(): ?string
    {
        return $this->ecologie;
    }

    public function setEcologie(?string $ecologie): self
    {
        $this->ecologie = $ecologie;

        return $this;
    }

    public function getSources(): ?string
    {
        return $this->sources;
    }

    public function setSources(?string $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function getProprietaire(): ?string
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?string $proprietaire): self
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function isDerniereVersion(): ?bool
    {
        return $this->derniere_version;
    }

    public function setDerniereVersion(bool $derniere_version): self
    {
        $this->derniere_version = $derniere_version;

        return $this;
    }


}
