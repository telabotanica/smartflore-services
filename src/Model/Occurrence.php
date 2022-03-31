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
     * @var Fiche[]
     */
    private $fiche;

    public function addFiche(Fiche $fiche) {
        $this->fiche[] = $fiche;
    }

    public function removeFiche(Fiche $fiche) {
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
     * @return Occurrence
     */
    public function setPosition(array $position): Occurrence
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Taxon
     */
    public function getTaxon(): Taxon
    {
        return $this->taxo;
    }

    /**
     * @param Taxon $taxon
     * @return Occurrence
     */
    public function setTaxon(Taxon $taxon): Occurrence
    {
        $this->taxo = $taxon;
        return $this;
    }
}
