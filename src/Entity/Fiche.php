<?php

namespace App\Entity;

use DateTimeInterface;
use App\Repository\FicheRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FicheRepository::class)]
class Fiche
{
    /**
     * @OA\Property(
     *     type="int",
     *     example="146"
     * )
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['show_fiche', 'list_fiche'])]
    private $id;

    /**
     * @OA\Property(
     *     type="string",
     *     example="SmartFloreBDTFXnt3363"
     * )
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['create_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $tag = null;

    /**
     * @OA\Property(
     *     type="int",
     *     example="3363"
     * )
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['create_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $nt = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['create_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $referentiel = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['show_fiche', 'list_fiche'])]
    private ?DateTimeInterface $date_modification = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="Plante vivace de 20-80 cm, velue, à souche épaisse et oblique\n- feuilles d'un vert terne\n- pédoncules hérissés\n- fleurs roses ou lilas..."
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['create_fiche', 'update_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $description = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="Plante amère et détersive."
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['create_fiche', 'update_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $usages = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="Champs, prés et côteaux, dans toute la France et en Corse."
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['create_fiche', 'update_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $ecologie = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="https://www.tela-botanica.org/bdtfx-nn-75201-synthese"
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['create_fiche', 'update_fiche', 'show_fiche', 'list_fiche'])]
    private ?string $sources = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="tela_user"
     * )
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['show_fiche', 'list_fiche'])]
    private ?string $proprietaire = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $user = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['show_fiche', 'list_fiche'])]
    private ?bool $derniere_version = null;



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

    public function getDateModification(): ?DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?DateTimeInterface $date_modification): self
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

    /**
     * @OA\Property(
     *     type="boolean",
     *     example=true
     * )
     */
    #[Groups(['show_fiche', 'list_fiche'])]
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
