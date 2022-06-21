<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Occurrence
{
    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.610769, "lon":3.876716}
     * )
     * @Groups ({"show_trail", "list_trail"})
     */
    private $position;

    /**
     * @var Taxon
     * @OA\Property(ref=@Model(type=Taxon::class))
     * @Groups ({"show_trail", "list_trail"})
     * @SerializedName("taxon")
     */
    private $taxo;

    /**
     * @var Image[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Image::class))
     * )
     * @Groups ({"show_trail", "list_trail"})
     */
    private $images;

    /**
     * @return float[]
     */
    public function getPosition(): ?array
    {
        return $this->position ? [
            'lat' => $this->position[1],
            'lng' => $this->position[0],
        ] : null;
    }

    /**
     * @param float $position
     * @return Occurrence
     */
    public function addPosition(float $position): Occurrence
    {
        $this->position[] = $position;
        return $this;
    }

    public function removePosition(float $position) {}

    /**
     * @return Taxon
     */
    public function getTaxo(): Taxon
    {
        return $this->taxo;
    }

    /**
     * @param Taxon $taxo
     * @return Occurrence
     */
    public function setTaxo(Taxon $taxo): Occurrence
    {
        $this->taxo = $taxo;
        return $this;
    }

    /**
     * @return array
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @param array $images
     * @return Occurrence
     */
    public function setImages(array $images): Occurrence
    {
        $this->images = $images;
        return $this;
    }

    /**
     * @Ignore()
     */
    public function getFirstImage()
    {
        return $this->images[0] ?? false;
    }
}
