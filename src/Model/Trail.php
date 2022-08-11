<?php

namespace App\Model;

use App\Service\TrailsService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Trail
{
    /**
     * @var int|string
     * @OA\Property(
     *     type="int",
     *     example="146"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     */
    private $id;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Arbres Remarquables"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail"})
     * @SerializedName("name")
     */
    private $nom;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Arbres vraiment remarquables mais genre de ouf tmtc"
     * )
     * @Groups({"show_trail", "list_trail"})
     * @SerializedName("display_name")
     */
    private $displayName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Tela Botanica"
     * )
     * @Groups({"show_trail", "list_trail"})
     * @SerializedName("author")
     */
    private $auteur;

    /**
     * @var int
     */
    private $authorId;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="draft"
     * )
     * @Groups({"user_trail"})
     */
    private $status;

    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="array", @OA\Items(type="float")),
     *     example={"start":{"lat":43.610769, "lon":3.876716}, "end":{"lat":43.610769, "lon":3.876716}}
     * )
     * @Groups({"show_trail", "list_trail"})
     */
    private $position;

    /**
     * @var ?Occurrence[] $occurrences
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Occurrence::class))
     * )
     * @Groups({"show_trail"})
     */
    private $occurrences;

    /**
     * @var ?int $occurrencesCount
     * @OA\Property(
     *     type="int",
     *     example="42"
     * )
     * @Groups({"show_trail", "list_trail"})
     */
    private $occurrencesCount;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="https://example.com/link+to+trail+details"
     * )
     * @Groups({"list_trail"})
     */
    private $details;

    /**
     * @var ?Image
     * @OA\Property(ref=@Model(type=Image::class))
     * @Groups({"show_trail", "list_trail"})
     */
    private $image;

    /**
     * @var Path
     * @SerializedName("path")
     * @Groups({"show_trail"})
     */
    private $chemin;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="420"
     * )
     * @Groups({"show_trail", "list_trail"})
     */
    private $pathLength;

    /**
     * @return int|string
     */
    public function getId()
    {
        return (int) $this->id;
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
     * @return int
     */
    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    /**
     * @param int $authorId
     * @return Trail
     */
    public function setAuthorId(int $authorId): Trail
    {
        $this->authorId = $authorId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Trail
     */
    public function setStatus(string $status): Trail
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return float[][]
     */
    public function getPosition(): array
    {
        $position = [
            'lat' => $this->position[1],
            'lng' => $this->position[0],
        ];

        // start and end will diverge in the future
        return [
            'start' => $position,
            'end' => $position,
        ];
    }

    /**
     * @return float[]
     */
    public function getStartPosition(): array
    {
        return [
            'lat' => $this->position[1],
            'lng' => $this->position[0],
        ];
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

    /**
     * @return ?Image
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * @param ?Image $image
     * @return Trail
     */
    public function setImage(?Image $image): Trail
    {
        $this->image = $image;
        return $this;
    }
    /**
     * @return Path
     */
    public function getChemin(): Path
    {
        return $this->chemin;
    }

    /**
     * @param Path $chemin
     * @return Trail
     */
    public function setChemin(Path $chemin): Trail
    {
        $this->chemin = $chemin;
        return $this;
    }

    /**
     * @return int
     */
    public function getPathLength(): int
    {
        if (!$this->pathLength) {
            $this->setPathLength(round(TrailsService::getTrailLength($this)));
        }

        return $this->pathLength;
    }

    /**
     * @param int $pathLength
     * @return Trail
     */
    public function setPathLength(int $pathLength): Trail
    {
        $this->pathLength = $pathLength;
        return $this;
    }
}
