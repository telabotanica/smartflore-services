<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CreateOccurrenceDto
{
    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.610769, "lon":3.876716}
     * )
     * @Assert\All(
     *     @Assert\Type("float")
     * )
     * @Assert\Count(
     *     min = 2,
     *     max = 2
     * )
     * @Groups({"create_trail"})
     */
    private $position;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre"
     * )
     * @Assert\NotNull
     * @Groups({"create_trail"})
     */
    private $scientificName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     * @Assert\NotBlank
     * @Groups({"create_trail"})
     */
    private $taxonRepository;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="131269"
     * )
     * @Groups({"create_trail"})
     */
    private $imageId;

    /**
     * @var string
     */
    private $cardTag;

    /**
     * @return float[]
     */
    public function getPosition(): array
    {
        return $this->position;
    }

    /**
     * @param float[] $position
     * @return CreateOccurrenceDto
     */
    public function setPosition(array $position): CreateOccurrenceDto
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string
     */
    public function getScientificName(): string
    {
        return $this->scientificName;
    }

    /**
     * @param string $scientificName
     * @return CreateOccurrenceDto
     */
    public function setScientificName(string $scientificName): CreateOccurrenceDto
    {
        $this->scientificName = $scientificName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTaxonRepository(): string
    {
        return $this->taxonRepository;
    }

    /**
     * @param string $taxonRepository
     * @return CreateOccurrenceDto
     */
    public function setTaxonRepository(string $taxonRepository): CreateOccurrenceDto
    {
        $this->taxonRepository = $taxonRepository;
        return $this;
    }

    /**
     * @return int
     */
    public function getImageId(): int
    {
        return $this->imageId;
    }

    /**
     * @param int $imageId
     * @return CreateOccurrenceDto
     */
    public function setImageId(int $imageId): CreateOccurrenceDto
    {
        $this->imageId = $imageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardTag(): string
    {
        return $this->cardTag;
    }

    /**
     * @param string $cardTag
     * @return CreateOccurrenceDto
     */
    public function setCardTag(string $cardTag): CreateOccurrenceDto
    {
        $this->cardTag = $cardTag;
        return $this;
    }
}
