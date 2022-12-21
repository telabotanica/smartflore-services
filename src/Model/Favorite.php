<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Favorite
{
    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="abcd@tela-botanica.org"
     * )
     * @Groups({"show_favorite", "list_favorite"})
     * @SerializedName("user")
     */
    private $user;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Acer campestre"
     * )
     * @Groups({"show_favorite", "list_favorite"})
     */
    private $scientificName;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="bdtfx"
     * )
     * @Assert\NotBlank
     * @Groups({"show_favorite", "list_favorite"})
     */
    private $taxonRepository;

    /**
     * @var int|string
     * @OA\Property(
     *     type="int",
     *     example="141"
     * )
     * @Assert\NotBlank
     * @Groups({"show_favorite", "list_favorite"})
     */
    private $taxonId;

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getScientificName(): string
    {
        return $this->scientificName;
    }

    /**
     * @param string $scientificName
     */
    public function setScientificName(string $scientificName): void
    {
        $this->scientificName = $scientificName;
    }

    /**
     * @return string
     */
    public function getTaxonRepository(): string
    {
        return $this->taxonRepository;
    }

    /**
     * @param string $taxonRepository
     */
    public function setTaxonRepository(string $taxonRepository): void
    {
        $this->taxonRepository = $taxonRepository;
    }

    /**
     * @return int|string
     */
    public function getTaxonId()
    {
        return $this->taxonId;
    }

    /**
     * @param int|string $taxonId
     */
    public function setTaxonId($taxonId): void
    {
        $this->taxonId = $taxonId;
    }


}