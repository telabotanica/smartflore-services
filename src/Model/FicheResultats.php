<?php

namespace App\Model;

use App\Entity\Fiche;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class FicheResultats
{
    /**
     * @var int | string
     * @OA\Property(
     *     type="int",
     *     example="8522"
     * )
     * @Groups({"list_fiche"})
     */
    private $num_taxonomique;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre"
     * )
     * @Groups({"list_fiche"})
     */
    private $nom_sci;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre L. [1753, Sp. Pl., 2 : 1055]"
     * )
     * @Groups({"list_fiche"})
     */
    private $nom_sci_complet;

    /**
     * @var bool
     * @OA\Property(
     *     type="bool",
     *     example="true"
     * )
     * @Groups({"list_fiche"})
     */
    private $retenu;

    /**
     * @var int | string
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     * @Groups({"list_fiche"})
     */
    private $num_nom;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     * @Groups({"list_fiche"})
     */
    private $referentiel;

    /**
     * @var string[] | null
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
     * @Groups({"list_fiche"})
     */
    private $noms_vernaculaires;

    /**
     * @var Fiche | null
     * @OA\Property(
     *     type="object",
     *     ref=@Model(type=Fiche::class, groups={"list_fiche"})
     * )
     * @Groups({"list_fiche"})
     */
    private $fiche;

    /**
     * @return int|string
     * @OA\Property(
     *     type="int",
     *     example="8522"
     * )
     * @Groups({"list_fiche"})
     */
    public function getNumTaxonomique()
    {
        return $this->num_taxonomique;
    }

    /**
     * @param int|string $num_taxonomique
     */
    public function setNumTaxonomique($num_taxonomique): void
    {
        $this->num_taxonomique = $num_taxonomique;
    }

    /**
     * @return string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre"
     * )
     * @Groups({"list_fiche"})
     */
    public function getNomSci(): string
    {
        return $this->nom_sci;
    }

    /**
     * @param string $nom_sci
     * @Groups({"list_fiche"})
     */
    public function setNomSci(string $nom_sci): void
    {
        $this->nom_sci = $nom_sci;
    }

    /**
     * @return string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre L. [1753, Sp. Pl., 2 : 1055]"
     * )
     * @Groups({"list_fiche"})
     */
    public function getNomSciComplet(): string
    {
        return $this->nom_sci_complet;
    }

    /**
     * @param string $nom_sci_complet
     */
    public function setNomSciComplet(string $nom_sci_complet): void
    {
        $this->nom_sci_complet = $nom_sci_complet;
    }

    /**
     * @return bool
     */
    public function isRetenu(): bool
    {
        return $this->retenu;
    }

    /**
     * @param bool $retenu
     */
    public function setRetenu(bool $retenu): void
    {
        $this->retenu = $retenu;
    }

    /**
     * @return int|string
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     * @Groups({"list_fiche"})
     */
    public function getNumNom()
    {
        return $this->num_nom;
    }

    /**
     * @param int|string $num_nom
     */
    public function setNumNom($num_nom): void
    {
        $this->num_nom = $num_nom;
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
     */
    public function setReferentiel(string $referentiel): void
    {
        $this->referentiel = $referentiel;
    }

    /**
     * @return string[]|null
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
     * @Groups({"list_fiche"})
     */
    public function getNomsVernaculaires(): ?array
    {
        return $this->noms_vernaculaires;
    }

    /**
     * @param string[]|null $noms_vernaculaires
     */
    public function setNomsVernaculaires(?array $noms_vernaculaires): void
    {
        $this->noms_vernaculaires = $noms_vernaculaires;
    }

    /**
     * @return Fiche|null
     */
    public function getFiche(): ?Fiche
    {
        return $this->fiche;
    }

    /**
     * @param Fiche|null $fiche
     */
    public function setFiche(?Fiche $fiche): void
    {
        $this->fiche = $fiche;
    }
}