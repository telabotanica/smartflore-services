<?php

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class Trail
{
    /**
     * @var int|string;
     * @Groups ({"show_trail", "list_trail"})
     */
    private $id;

    /**
     * @var string;
     * @Groups ({"show_trail", "list_trail"})
     */
    private $nom;

    /**
     * @var string;
     * @Groups ({"show_trail", "list_trail"})
     */
    private $displayName;

    /**
     * @var string;
     * @Groups ({"show_trail", "list_trail"})
     */
    private $auteur;

    /**
     * @var float[];
     * @Groups ({"show_trail", "list_trail"})
     */
    private $position;

    /**
     * @var ?Occurrence[] $occurrences
     * @Groups ({"show_trail"})
     */
    private $occurrences;

    /**
     * @var ?int $occurrencesCount
     * @Groups ({"show_trail", "list_trail"})
     */
    private $occurrencesCount;

    /**
     * @var string;
     * @Groups ({"list_trail"})
     */
    private $details;

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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
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
     * @return Occurrence[]|null
     */
    public function getOccurrences(): ?array
    {
        return $this->occurrences;
    }

    public function addOccurrence(Occurrence $occurrence): void
    {
        $this->occurrences[] = $occurrence;
        $this->setOccurrencesCount(count($this->occurrences));
    }

    public function removeOccurrence(Occurrence $occurrence): void
    {}

//    public function hasOccurrence()
//    {
//        return count($this->occurrences) > 0;
//    }

//    /**
//     * @param Occurrence[]|null $occurrences
//     * @return Trail
//     */
//    public function setOccurrences(?array $occurrences): Trail
//    {
//        $this->occurrences = $occurrences;
//        return $this;
//    }

    /**
     * @return int|null
     */
    public function getOccurrencesCount(): ?int
    {
        return $this->occurrencesCount;
    }

    /**
     * @param int|null $occurrencesCount
     * @return Trail
     */
    public function setOccurrencesCount(?int $occurrencesCount): Trail
    {
        $this->occurrencesCount = $occurrencesCount;
        return $this;
    }

    public function computeOccurrencesCount(): void
    {
        $this->setOccurrencesCount(count($this->getOccurrences()));
    }

    /**
     * @return string
     */
    public function getDetails(): string
    {
        return $this->details;
    }

    /**
     * @param string $details
     * @return Trail
     */
    public function setDetails(string $details): Trail
    {
        $this->details = $details;
        return $this;
    }
}
