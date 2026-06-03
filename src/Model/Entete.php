<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Entete
{
    /**
     * @var int | string
     * @OA\Property(
     *     type="int",
     *     example="52"
     * )
     * @Groups({"list_fiche"})
     */
    private $total;

    /**
     * @var int | string
     * @OA\Property(
     *     type="int",
     *     example="0"
     * )
     * @Groups({"list_fiche"})
     */
    private $depart;

    /**
     * @var int | string
     * @OA\Property(
     *     type="int",
     *     example="10"
     * )
     * @Groups({"list_fiche"})
     */
    private $limite;

    /**
     * @var string | null
     * @OA\Property(
     *     type="int",
     *     example="referentiel=bdtfx&num_tax=141"
     * )
     * @Groups({"list_fiche"})
     */
    private $masque;

    /**
     * @var string | null
     * @OA\Property(
     *     type="int",
     *     example="http://127.0.0.1:8000/fiche?limite=10&debut=20&referentiel=bdtfx&num_tax=141"
     * )
     * @Groups({"list_fiche"})
     */
    private $href_suivant;

    /**
     * @var string | null
     * @OA\Property(
     *     type="int",
     *     example="http://127.0.0.1:8000/fiche?limite=10&debut=0&referentiel=bdtfx&num_tax=141"
     * )
     * @Groups({"list_fiche"})
     */
    private $href_precedent;

    /**
     * @return int
     *
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getDepart(): int
    {
        return $this->depart;
    }

    /**
     * @param int $depart
     */
    public function setDepart(int $depart): void
    {
        $this->depart = $depart;
    }

    /**
     * @return int
     */
    public function getLimite(): int
    {
        return $this->limite;
    }

    /**
     * @param int $limite
     */
    public function setLimite(int $limite): void
    {
        $this->limite = $limite;
    }

    /**
     * @return string|null
     */
    public function getMasque(): ?string
    {
        return $this->masque;
    }

    /**
     * @param string|null $masque
     */
    public function setMasque(?string $masque): void
    {
        $this->masque = $masque;
    }

    /**
     * @return string|null
     */
    public function getHrefSuivant(): ?string
    {
        return $this->href_suivant;
    }

    /**
     * @param string|null $href_suivant
     */
    public function setHrefSuivant(?string $href_suivant): void
    {
        $this->href_suivant = $href_suivant;
    }

    /**
     * @return string|null
     */
    public function getHrefPrecedent(): ?string
    {
        return $this->href_precedent;
    }

    /**
     * @param string|null $href_precedent
     */
    public function setHrefPrecedent(?string $href_precedent): void
    {
        $this->href_precedent = $href_precedent;
    }


}