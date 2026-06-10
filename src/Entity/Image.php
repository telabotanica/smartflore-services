<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    /** @OA\Property(
     *     type="int",
     *     example="131269"
     * )
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['show_trail', 'list_trail', 'user_trail', 'show_taxon', 'user_trail', 'update_occurrence'])]
    private ?int $id = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="https://api.tela-botanica.org/img:002221908M.jpg"
     * )
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['show_trail', 'list_trail', 'user_trail', 'show_taxon', 'user_trail', 'create_trail', 'update_occurrence', 'update_image'])]
    private ?string $url = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="Jean Michel Photographe"
     * )
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['show_trail', 'list_trail', 'user_trail', 'show_taxon', 'user_trail', 'create_trail', 'update_occurrence', 'update_image'])]
    private ?string $author = null;

    #[ORM\ManyToOne(targetEntity: Occurrence::class, inversedBy: 'images', cascade: ['persist'])]
    private ?Occurrence $occurrence = null;

    /**
     * @OA\Property(
     *     type="integer",
     *     example=131269
     * )
     */
    #[ORM\Column(type: 'integer', length: 255, nullable: true)]
    #[Groups(['show_trail', 'list_trail', 'user_trail', 'show_taxon', 'user_trail', 'create_trail', 'update_occurrence', 'update_image'])]
    private ?int $cel_image_id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['show_trail', 'list_trail', 'user_trail', 'show_taxon', 'user_trail', 'create_trail', 'update_occurrence', 'update_image'])]
    private ?string $mini = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getOccurrence(): ?Occurrence
    {
        return $this->occurrence;
    }

    public function setOccurrence(?Occurrence $occurrence): self
    {
        $this->occurrence = $occurrence;

        return $this;
    }

    /**
     * @OA\Property(
     *     type="integer",
     *     example=131269
     * )
     */
    #[SerializedName('image_id')]
    #[Groups(['create_trail', 'update_occurrence'])]
    public function getCelImageId(): ?int
    {
        return $this->cel_image_id;
    }

    public function setCelImageId(?int $cel_image_id): self
    {
        $this->cel_image_id = $cel_image_id;

        return $this;
    }

    public function getMini(): ?string
    {
        return $this->mini;
    }

    public function setMini(?string $mini): self
    {
        $this->mini = $mini;

        return $this;
    }
}
