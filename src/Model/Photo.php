<?php

namespace App\Model;

use App\Service\TrailsService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Photo
{
    /**
     * @var int|string
     * @OA\Property(
     *     type="int",
     *     example="146"
     * )
     */
    private $id;

    /**
     * @var string
     */
    private $imageNom;

    /**
     * @var Taxon
     * @OA\Property(ref=@Model(type=Taxon::class))
     * @Groups({"create_photo", "user_photo"})
     * @SerializedName("taxon")
     */
    private $taxo;

    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.6082423, "lon":3.8800137}
     * )
     * @Groups({"create_photo", "user_photo"})
     */
    private $position;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="10/01/2023"
     * )
     * @Groups({"create_photo", "user_photo"})
     */
    private $date;

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getImageNom(): string
    {
        return $this->imageNom;
    }

    /**
     * @param string $imageNom
     */
    public function setImageNom(string $imageNom): void
    {
        $this->imageNom = $imageNom;
    }

    /**
     * @return Taxon
     */
    public function getTaxo(): Taxon
    {
        return $this->taxo;
    }

    /**
     * @param Taxon $taxo
     */
    public function setTaxo(Taxon $taxo): void
    {
        $this->taxo = $taxo;
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
     */
    public function setPosition(array $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }


}
