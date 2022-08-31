<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
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
     * @SerializedName("scientific_name")
     * @Groups({"show_trail", "show_taxon"})
     */
    private $espece;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre L."
     * )
     * @Groups({"show_trail", "show_taxon"})
     */
    private $fullScientificName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="<span class=""sci""><span class=""gen"">Acer</span> <span class=""sp"">campestre</span></span> <span class=""auteur"">L.</span> [<span class=""annee"">1753</span>, <span class=""biblio"">Sp. Pl., 2 : 1055</span>]"
     * )
     * @Groups({"show_taxon"})
     */
    private $htmlFullScientificName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer"
     * )
     * @SerializedName("genus")
     * @Groups({"show_taxon"})
     */
    private $genre;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Sapindaceae"
     * )
     * @SerializedName("family")
     * @Groups({"show_taxon"})
     */
    private $famille;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     * @SerializedName("taxon_repository")
     * @Groups({"show_trail", "show_taxon"})
     */
    private $referentiel;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     * @SerializedName("name_id")
     * @Groups({"show_trail", "show_taxon"})
     */
    private $numNom;

    /**
     * @var int
     */
    private $acceptedScientificNameId;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="8522"
     * )
     * @Groups({"show_taxon"})
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
     * @Groups({"show_trail", "show_taxon"})
     */
    private $vernacularNames;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="https://fr.wikipedia.org/wiki/Acer_campestre"
     * )
     * @Groups({"show_taxon"})
     */
    private $wikipediaUrl;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="https://www.tela-botanica.org/widget:cel:cartoPoint?num_nom_ret=141&referentiel=bdtfx"
     * )
     * @Groups({"show_taxon"})
     */
    private $mapUrl;

    /**
     * @var Card
     * @OA\Property(ref=@Model(type=Card::class))
     * @Groups({"show_taxon"})
     */
    private $card;

    /**
     * @var Image[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Image::class))
     * )
     * @Groups({"show_taxon"})
     */
    private $images;

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
    public function getFullScientificName(): string
    {
        return $this->fullScientificName;
    }

    /**
     * @param string $fullScientificName
     * @return Taxon
     */
    public function setFullScientificName(string $fullScientificName): Taxon
    {
        $this->fullScientificName = $fullScientificName;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlFullScientificName(): string
    {
        return $this->htmlFullScientificName;
    }

    /**
     * @param string $htmlFullScientificName
     * @return Taxon
     */
    public function setHtmlFullScientificName(string $htmlFullScientificName): Taxon
    {
        $this->htmlFullScientificName = $htmlFullScientificName;
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
    public function getAcceptedScientificNameId(): int
    {
        return $this->acceptedScientificNameId;
    }

    /**
     * @param int $acceptedScientificNameId
     * @return Taxon
     */
    public function setAcceptedScientificNameId(int $acceptedScientificNameId): Taxon
    {
        $this->acceptedScientificNameId = $acceptedScientificNameId;
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
        return array_values($this->vernacularNames ?? []);
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
            $previous = $this->vernacularNames[$order] ?? null;
            $this->vernacularNames[$order] = $vernacularName;
            if ($previous) {
                $this->vernacularNames[] = $previous;
            }
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

    /**
     * @return string
     */
    public function getMapUrl(): string
    {
        return $this->mapUrl
            ?? sprintf(
                'https://www.tela-botanica.org/widget:cel:cartoPoint?referentiel=%s&num_nom_ret=%s',
                $this->getReferentiel(),
                $this->getNumNom()
            );
    }

    /**
     * @param string $mapUrl
     * @return Taxon
     */
    public function setMapUrl(string $mapUrl): Taxon
    {
        $this->mapUrl = $mapUrl;
        return $this;
    }

    /**
     * @return Card
     */
    public function getCard(): Card
    {
        if (!$this->card) {
            $this->card = new Card();
        }

        return $this->card;
    }

    /**
     * @param Card $card
     * @return Taxon
     */
    public function setCard(Card $card): Taxon
    {
        $this->card = $card;
        return $this;
    }

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @param Image[] $images
     * @return Taxon
     */
    public function setImages(array $images): Taxon
    {
        $this->images = $images;
        return $this;
    }
}
