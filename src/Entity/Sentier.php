<?php

namespace App\Entity;

use App\Entity\Path;
use App\Repository\SentierRepository;
use App\Service\TrailsService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SentierRepository::class)
 */
class Sentier
{
    const PRM_VALUES = [
        -1, // don't know
        0,  // not accessible
        1   // accessible
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @OA\Property(
     *     type="int",
     *     example="146"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @OA\Property(
     *     type="string",
     *     example="Arbres Remarquables"
     * )
     * @Groups({"create_trail", "update_trail", "list_trail"})
     * @SerializedName("name")
     */
    private $nom;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Arbres vraiment remarquables mais genre de ouf tmtc"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     * @SerializedName("display_name")
     */
    private $displayName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $authorId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(
     *     type="string",
     *     example="Tela Botanica"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     * @SerializedName("author")
     */
    private $auteur;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(
     *     type="string",
     *     example="draft"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $status;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @OA\Property(
     *     type="object",
     *     @OA\Property(
     *         property="start",
     *         type="object",
     *         @OA\Property(property="lat", type="number", format="float"),
     *         @OA\Property(property="lng", type="number", format="float")
     *     ),
     *     @OA\Property(
     *         property="end",
     *         type="object",
     *         @OA\Property(property="lat", type="number", format="float"),
     *         @OA\Property(property="lng", type="number", format="float")
     *     ),
     *     example={
     *         "start": {"lat": 43.610769, "lng": 3.876716},
     *         "end": {"lat": 43.610769, "lng": 3.876716}
     *     }
     * )
     * @Groups({"show_trail", "list_trail", "user_trail", "create_trail", "update_trail"})
     */
    private $position = [];

    /**
     * @ORM\OneToOne(targetEntity=Path::class, cascade={"persist", "remove"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     * @SerializedName("path")
     * @Groups({"show_trail", "create_trail", "update_trail", "list_trail"})
     * @OA\Property(ref=@Model(type=Path::class))
     */
    private $chemin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @OA\Property(
     *     type="int",
     *     example="420"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $pathLength;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @OA\Property(
     *     type="int",
     *     example="42"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $occurrencesCount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(
     *     type="string",
     *     example="https://example.com/link+to+trail+details"
     * )
     * @Groups({"list_trail", "user_trail", "show_trail"})
     */
    private $details;

    /**
     * @ORM\Column(type="integer", nullable=true)
     ** @OA\Property(
     *     type="int",
     *     example="-1",
     *     description="Is the trail accessible to person with reduced mobility ? -1 = don't know, 0 = no, 1 = yes"
     * )
     * @Assert\Type("int")
     * @Assert\Range(
     *     min = -1,
     *     max = 1
     * )
     * @Groups({"show_trail", "list_trail", "user_trail","create_trail", "update_trail"})
     * @SerializedName("prm")
     * values : // -1 = don't know // 0 = no // 1 = yes
     */
    private $pmr;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="boolean"),
     *     example={true, false, false, true},
     *     description="Which seasons are the best to visit this sentier? 4 booleans: spring, summer, autumn, winter"
     * )
     * @Assert\All({
     *     @Assert\Type("bool")
     * })
     * @Assert\Count(
     *     min=4,
     *     max=4,
     *     exactMessage="Vous devez spécifier exactement 4 saisons"
     * )
     * @SerializedName("best_season")
     * @Groups({"show_trail", "list_trail", "user_trail","create_trail", "update_trail"})
     */
    private $meilleures_saisons = [];

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $date_creation;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $date_modification;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_suppression;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $date_publication;

     /**
     * @ORM\Column(type="integer", nullable=true)
      * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $nb_taxons;

    /**
     * @ORM\OneToMany(targetEntity=Occurrence::class, mappedBy="sentier", cascade={"persist"}, fetch="EAGER")
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Occurrence::class)),
     * )
     * @Groups({"show_trail", "list_trail", "create_trail"})
     */
    private $occurrences;

    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDisplayName(): string
    {
        $this->displayName = $this->displayName ?: $this->getNom();
        // mb_ucfirst
        $firstChar = mb_substr($this->displayName, 0, 1);
        $then = mb_substr($this->displayName, 1);
        return mb_strtoupper($firstChar) . $then;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    public function setAuthorId(string $authorId): self
    {
        $this->authorId = $authorId;
        return $this;
    }

    public function getAuteur(): string
    {
        return $this->auteur;
    }

    public function setAuteur(string $auteur): self
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPosition(): ?array
    {
        if ($this->position && array_key_exists('1', $this->position)){
            $position = [
                'lat' => $this->position[1],
                'lng' => $this->position[0],
            ];

            // start and end will diverge in the future
            return [
                'start' => $position,
                'end' => $position,
            ];
        } else {
            return $this->position;
        }
    }

    public function getStartPosition(): array
    {
        return [
            'lat' => $this->position["start"]['lat'],
            'lng' => $this->position["start"]['lng']
        ];
    }

    public function setPosition(array $position): self
    {
        // Cas 1 : format start/end
        if (
            isset($position['start']['lat'], $position['start']['lng']) &&
            isset($position['end']['lat'], $position['end']['lng'])
        ) {
            $this->position = $position;
        }
        // Cas 2 : format [lng, lat]
        elseif (array_keys($position) === [0, 1] && is_numeric($position[0]) && is_numeric($position[1])) {
            $this->position = $position;
        }
        else {
            throw new \InvalidArgumentException('Invalid position format: expected [lng, lat] or start/end structure.');
        }

        return $this;
    }

    public function getChemin(): ?Path
    {
        return $this->chemin;
    }

    public function getPathLength(): int
    {
        if (!$this->pathLength) {
            $this->setPathLength(round(TrailsService::getTrailLength($this)));
        }

        return $this->pathLength;
    }

    public function setPathLength(int $pathLength): self
    {
        $this->pathLength = $pathLength;
        return $this;
    }

    public function setChemin(Path $chemin): self
    {
        $this->chemin = $chemin;
        return $this;
    }

    public function getOccurrencesCount(): ?int
    {
        return $this->occurrencesCount;
    }

    public function setOccurrencesCount(?int $occurrencesCount): self
    {
        $this->occurrencesCount = $occurrencesCount;

        return $this;
    }

    //    public function computeOccurrencesCount(): void
//    {
//        $this->setOccurrencesCount(count($this->getOccurrences()));
//    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getPmr(): ?int
    {
        return $this->pmr;
    }

    public function setPmr(?int $pmr): self
    {
        if (!in_array($pmr, self::PRM_VALUES)) {
            throw new \InvalidArgumentException(
                "Given PRM value : $pmr is not in allowed range : ".implode(', ', self::PRM_VALUES));
        }
        $this->pmr = $pmr;
        return $this;
    }

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="boolean"),
     *     example={true, false, false, true},
     *     description="Which seasons are the best to visit this sentier? 4 booleans: spring, summer, autumn, winter"
     * )
     * @Assert\All({
     *     @Assert\Type("bool")
     * })
     * @Assert\Count(
     *     min=4,
     *     max=4,
     *     exactMessage="Vous devez spécifier exactement 4 saisons"
     * )
     * @SerializedName("best_season")
     * @Groups({"show_trail", "list_trail", "user_trail","create_trail", "update_trail"})
     */
    public function getMeilleuresSaisons(): ?array
    {
        return $this->meilleures_saisons;
    }

    public function setMeilleuresSaisons(?array $meilleures_saisons): self
    {
        if (count($meilleures_saisons) !== 4) {
            throw new \InvalidArgumentException(
                'Best season array should contain exactly 4 elements instead of given '.count($meilleures_saisons));
        }
        foreach ($meilleures_saisons as $season) {
            if (!is_bool($season)) {
                throw new \InvalidArgumentException(
                    'Best season array should contain only boolean instead of given '.gettype($season));
            }
        }
        $this->meilleures_saisons = $meilleures_saisons;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;

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

    public function getDateSuppression(): ?\DateTimeInterface
    {
        return $this->date_suppression;
    }

    public function setDateSuppression(?\DateTimeInterface $date_suppression): self
    {
        $this->date_suppression = $date_suppression;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTimeInterface $date_publication): self
    {
        $this->date_publication = $date_publication;

        return $this;
    }

    public function getNbTaxons(): ?int
    {
        return $this->nb_taxons;
    }

    public function setNbTaxons(?int $nb_taxons): self
    {
        $this->nb_taxons = $nb_taxons;

        return $this;
    }

    /**
     * @return Collection<int, Occurrence>
     */
    public function getOccurrences(): Collection
    {
        return $this->occurrences;
    }

//    public function setOccurrences(array $occurrences): self
//    {
//        $this->occurrences = new ArrayCollection($occurrences);
//        return $this;
//    }

    public function addOccurrence(Occurrence $occurrence): self
    {
        if (!$this->occurrences->contains($occurrence)) {
            $this->occurrences[] = $occurrence;
            $occurrence->setSentier($this);
        }

        return $this;
    }

    public function removeOccurrence(Occurrence $occurrence): self
    {
        if ($this->occurrences->removeElement($occurrence)) {
            // set the owning side to null (unless already changed)
            if ($occurrence->getSentier() === $this) {
                $occurrence->setSentier(null);
            }
        }

        return $this;
    }


}
