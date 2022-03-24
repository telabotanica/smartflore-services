<?php

namespace App\Model;

class Occurrence
{
    /**
     * @var float[]
     */
    private $position;

    /**
     * @var Taxo
     */
    private $taxo;

    /**
     * @var Fiche
     */
    private $fiche;

    public function addFiche(Fiche $fiche) {
        $this->fiche[] = $fiche;
    }

    public function removeFiche(Fiche $fiche) {
    }

}
