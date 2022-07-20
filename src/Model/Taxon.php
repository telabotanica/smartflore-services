<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Taxon
{
    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre"
     * )
     * @SerializedName("species")
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $espece;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="L."
     * )
     * @SerializedName("author")
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $auteurEspece;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre L."
     * )
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $fullName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="<span class=""sci""><span class=""gen"">Acer</span> <span class=""sp"">campestre</span></span> <span class=""auteur"">L.</span> [<span class=""annee"">1753</span>, <span class=""biblio"">Sp. Pl., 2 : 1055</span>]"
     * )
     * @Groups ({"show_taxon"})
     */
    private $htmlCompleteName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer"
     * )
     * @SerializedName("genus")
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $genre;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Sapindaceae"
     * )
     * @SerializedName("family")
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $famille;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     * @SerializedName("referential")
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $referentiel;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     * @SerializedName("name_id")
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $numNom;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="8522"
     * )
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $taxonomicId;

    /**
     * @var string[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="string"
     *     ),
     *     example={
     *         "Acéraille",
     *         "Érable champêtre"
     *     }
     * )
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $vernacularNames;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="https://fr.wikipedia.org/wiki/Acer_campestre"
     * )
     * @Groups ({"show_trail", "show_taxon"})
     */
    private $wikipediaUrl;

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
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     * @return Taxon
     */
    public function setFullName(string $fullName): Taxon
    {
        $this->fullName = $fullName;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlCompleteName(): string
    {
        return $this->htmlCompleteName;
    }

    /**
     * @param string $htmlCompleteName
     * @return Taxon
     */
    public function setHtmlCompleteName(string $htmlCompleteName): Taxon
    {
        $this->htmlCompleteName = $htmlCompleteName;
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

    /**
     * @return int
     */
    public function getTaxonomicId(): int
    {
        return $this->taxonomicId;
    }

    /**
     * @param int $taxonomicId
     * @return Taxon
     */
    public function setTaxonomicId(int $taxonomicId): Taxon
    {
        $this->taxonomicId = $taxonomicId;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getVernacularNames(): array
    {
        return $this->vernacularNames;
    }

    /**
     * @param string[] $vernacularNames
     * @return Taxon
     */
    public function setVernacularNames(array $vernacularNames): Taxon
    {
        $this->vernacularNames = $vernacularNames;
        return $this;
    }

    public function addVernacularName(string $vernacularName, int $order = 0)
    {
        if ($order) {
            $this->vernacularNames[$order] = $vernacularName;
        } else {
            $this->vernacularNames[] = $vernacularName;
        }
    }

    /**
     * @return string
     */
    public function getWikipediaUrl(): string
    {
        return $this->wikipediaUrl
            ?? 'https://fr.wikipedia.org/wiki/'.str_replace (' ', '_', $this->espece);
    }

    /**
     * @param string $wikipediaUrl
     * @return Taxon
     */
    public function setWikipediaUrl(string $wikipediaUrl): Taxon
    {
        $this->wikipediaUrl = $wikipediaUrl;
        return $this;
    }
}
