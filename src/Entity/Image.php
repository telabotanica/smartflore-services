<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass=ImageRepository::class)
 */
class Image
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *  @OA\Property(
     *     type="int",
     *     example="131269"
     * )
     * @Groups({"show_trail", "list_trail", "show_taxon", "user_trail", "create_trail"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(
     *     type="string",
     *     example="https://api.tela-botanica.org/img:002221908M.jpg"
     * )
     * @Groups({"show_trail", "list_trail", "show_taxon", "user_trail"})
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(
     *     type="string",
     *     example="Jean Michel Photographe"
     * )
     * @Groups({"show_trail", "list_trail", "show_taxon", "user_trail"})
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user_id;

    /**
     * @ORM\ManyToOne(targetEntity=Occurrence::class, inversedBy="images")
     */
    private $occurrence;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function setUserId(?string $user_id): self
    {
        $this->user_id = $user_id;

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
}
