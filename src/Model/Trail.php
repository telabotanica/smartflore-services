<?php

namespace App\Model;

class Trail
{
    /**
     * @var int|string;
     */
    private $id;

    /**
     * @var string;
     */
    private $nom;

    /**
     * @var string;
     */
    private $auteur;

    /**
     * @var float[];
     */
    private $position;

    /**
     * @var \DateTime;
     */
    private $dateCreation;

    /**
     * @var \DateTime;
     */
    private $dateModification;

    /**
     * @var ?\DateTime;
     */
    private $dateSuppression;

    /**
     * @var ?Occurrence[]
     */
    private $occurrences;

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return Trail
     */
    public function setId($id): Trail
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * @param string $nom
     * @return Trail
     */
    public function setNom(string $nom): Trail
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuteur(): string
    {
        return $this->auteur;
    }

    /**
     * @param string $auteur
     * @return Trail
     */
    public function setAuteur(string $auteur): Trail
    {
        $this->auteur = $auteur;
        return $this;
    }

    /**
     * @return float[]
     */
    public function getPosition(): array
    {
        return $this->position;
    }

    /**
     * @param float[] $position
     * @return Trail
     */
    public function setPosition(array $position): Trail
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreation(): \DateTime
    {
        return $this->dateCreation;
    }

    /**
     * @param \DateTime $dateCreation
     * @return Trail
     */
    public function setDateCreation(\DateTime $dateCreation): Trail
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateModification(): \DateTime
    {
        return $this->dateModification;
    }

    /**
     * @param \DateTime $dateModification
     * @return Trail
     */
    public function setDateModification(\DateTime $dateModification): Trail
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateSuppression(): ?\DateTime
    {
        return $this->dateSuppression;
    }

    /**
     * @param \DateTime|null $dateSuppression
     * @return Trail
     */
    public function setDateSuppression(?\DateTime $dateSuppression): Trail
    {
        $this->dateSuppression = $dateSuppression;
        return $this;
    }

    /**
     * @return Occurrence[]|null
     */
    public function getOccurrences(): ?array
    {
        return $this->occurrences;
    }

    public function addOccurrence(Occurrence $occurrence) {
        $this->occurrences[] = $occurrence;
    }

    public function removeOccurrence(Occurrence $occurrence) {}

//    /**
//     * @param Occurrence[]|null $occurrences
//     * @return Trail
//     */
//    public function setOccurrences(?array $occurrences): Trail
//    {
//        $this->occurrences = $occurrences;
//        return $this;
//    }
}
