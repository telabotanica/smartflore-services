<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Referentiel
{
    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="BDTFX"
     * )
     * @Groups({"list_referentiel", "show_taxon"})
     */
    private $nom;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="France métropolitaine"
     * )
     * @Groups({"list_referentiel", "show_taxon"})
     */
    private $label;

    /**
     * @var string|null
     * @OA\Property(
     *     type="string",
     *     example="nvjfl"
     * )
     * @Groups({"list_referentiel", "show_taxon"})
     */
    private $nomVernaculaire;

    /**
     * @var string|null
     * @OA\Property(
     *     type="string",
     *     example="null"
     * )
     * @Groups({"list_referentiel", "show_taxon"})
     */
    private $filtre;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="null"
     * )
     * @Groups({"list_referentiel", "show_taxon"})
     */
    private $fournisseur_fiches_especes;

    /**
     * @return string
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * @param string $nom
     */
    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string|null
     */
    public function getNomVernaculaire(): ?string
    {
        return $this->nomVernaculaire;
    }

    /**
     * @param string|null $nomVernaculaire
     */
    public function setNomVernaculaire(?string $nomVernaculaire): void
    {
        $this->nomVernaculaire = $nomVernaculaire;
    }

    /**
     * @return string|null
     */
    public function getFiltre(): ?string
    {
        return $this->filtre;
    }

    /**
     * @param string|null $filtre
     */
    public function setFiltre(?string $filtre): void
    {
        $this->filtre = $filtre;
    }

    /**
     * @return string
     */
    public function getFournisseurFichesEspeces(): string
    {
        return $this->fournisseur_fiches_especes;
    }

    /**
     * @param string $fournisseur_fiches_especes
     */
    public function setFournisseurFichesEspeces(string $fournisseur_fiches_especes): void
    {
        $this->fournisseur_fiches_especes = $fournisseur_fiches_especes;
    }




}