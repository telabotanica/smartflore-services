<?php

namespace App\Model;

class Occurrence
{
    /**
     * @var float[]
     */
    private $position;

    /**
     * @var Taxon
     */
    private $taxo;

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
}
