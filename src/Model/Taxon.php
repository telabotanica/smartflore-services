<?php

namespace App\Model;

class Taxon
{
    /**
     * @var string
     */
    private $espece;

    /**
     * @var string
     */
    private $auteurEspece;

    /**
     * @var string
     */
    private $auteurGenre;

    /**
     * @var string
     */
    private $auteurFamille;

    /**
     * @var string
     */
    private $genre;

    /**
     * @var string
     */
    private $famille;

    /**
     * @var string
     */
    private $referentiel;

    /**
     * @var int
     */
    private $numNom;

    /**
     * @return string
     */
    public function getEspece(): string
    {
        return $this->espece;
    }

    /**
     * @param string $espece
     * @return Taxon
     */
    public function setEspece(string $espece): Taxon
    {
        $this->espece = $espece;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuteurEspece(): string
    {
        return $this->auteurEspece;
    }

    /**
     * @param string $auteurEspece
     * @return Taxon
     */
    public function setAuteurEspece(string $auteurEspece): Taxon
    {
        $this->auteurEspece = $auteurEspece;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuteurGenre(): string
    {
        return $this->auteurGenre;
    }

    /**
     * @param string $auteurGenre
     * @return Taxon
     */
    public function setAuteurGenre(string $auteurGenre): Taxon
    {
        $this->auteurGenre = $auteurGenre;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuteurFamille(): string
    {
        return $this->auteurFamille;
    }

    /**
     * @param string $auteurFamille
     * @return Taxon
     */
    public function setAuteurFamille(string $auteurFamille): Taxon
    {
        $this->auteurFamille = $auteurFamille;
        return $this;
    }

    /**
     * @return string
     */
    public function getGenre(): string
    {
        return $this->genre;
    }

    /**
     * @param string $genre
     * @return Taxon
     */
    public function setGenre(string $genre): Taxon
    {
        $this->genre = $genre;
        return $this;
    }

    /**
     * @return string
     */
    public function getFamille(): string
    {
        return $this->famille;
    }

    /**
     * @param string $famille
     * @return Taxon
     */
    public function setFamille(string $famille): Taxon
    {
        $this->famille = $famille;
        return $this;
    }

    /**
     * @return string
     */
    public function getReferentiel(): string
    {
        return $this->referentiel;
    }

    /**
     * @param string $referentiel
     * @return Taxon
     */
    public function setReferentiel(string $referentiel): Taxon
    {
        $this->referentiel = $referentiel;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumNom(): int
    {
        return $this->numNom;
    }

    /**
     * @param int $numNom
     * @return Taxon
     */
    public function setNumNom(int $numNom): Taxon
    {
        $this->numNom = $numNom;
        return $this;
    }
}
