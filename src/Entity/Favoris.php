<?php

namespace App\Entity;

use App\Repository\FavorisRepository;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
class Favoris
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['show_favorite', 'list_favorite'])]
    private $id;

    /**
     * @OA\Property(
     *     type="string",
     *     example="abcd@tela-botanica.org"
     * )
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['show_favorite', 'list_favorite'])]
    private ?string $user_email = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['show_favorite', 'list_favorite'])]
    private ?string $user_id = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre"
     * )
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['show_favorite', 'list_favorite'])]
    private ?string $scientific_name = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['show_favorite', 'list_favorite', 'add_favorite'])]
    private ?string $referentiel = null;

    /**
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Groups(['show_favorite', 'list_favorite', 'add_favorite'])]
    private ?int $taxon_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @OA\Property(
     *     type="string",
     *     example="abcd@tela-botanica.org"
     * )
     */
    #[Groups(['show_favorite', 'list_favorite'])]
    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function setUserEmail(string $user_email): self
    {
        $this->user_email = $user_email;

        return $this;
    }

    /**
     * @OA\Property(
     *     type="string",
     *     example="12345"
     * )
     */
    #[Groups(['show_favorite', 'list_favorite'])]
    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function setUserId(string $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre L."
     * )
     */
    #[Groups(['show_favorite', 'list_favorite'])]
    public function getScientificName(): ?string
    {
        return $this->scientific_name;
    }

    public function setScientificName(?string $scientific_name): self
    {
        $this->scientific_name = $scientific_name;

        return $this;
    }

    public function getReferentiel(): ?string
    {
        return $this->referentiel;
    }

    public function setReferentiel(string $referentiel): self
    {
        $this->referentiel = $referentiel;

        return $this;
    }

    /**
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     */
    #[Groups(['show_favorite', 'list_favorite', 'add_favorite'])]
    public function getTaxonId(): ?int
    {
        return $this->taxon_id;
    }

    public function setTaxonId(int $taxon_id): self
    {
        $this->taxon_id = $taxon_id;

        return $this;
    }
}
