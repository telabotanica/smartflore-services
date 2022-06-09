<?php

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Occurrence
{
    /**
     * @var float[]
     */
    private $position;

    /**
     * @var Taxon
     * @SerializedName("taxon")
     */
    private $taxo;

    /**
     * @var array
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
}
