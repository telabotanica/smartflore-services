<?php

namespace App\Entity;

use App\Model\Taxon;
use App\Repository\OccurrenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass=OccurrenceRepository::class)
 */
class Occurrence
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Sentier::class, inversedBy="occurrences")
     */
    private $sentier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $card_tag;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @OA\Property(
     *     type="object",
     *     example={"lat":43.610769, "lng":3.876716},
     *     @OA\Property(property="lat", type="number", format="float"),
     *     @OA\Property(property="lng", type="number", format="float")
     * )
     * @Groups({"show_trail", "list_trail", "create_trail"})
     */
    private $position;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @OA\Property(
     *     type="text",
     *     example="Cet arbre a été planté par Napoléon"
     * )
     * @Groups({"show_trail", "list_trail", "create_trail"})
     */
    private $anecdotes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user_id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_suppression;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @OA\Property(ref=@Model(type=Taxon::class))
     * @Groups({"show_trail", "list_trail", "create_trail"})
     * @SerializedName("taxon")
     */
    private $taxon = [];

    /**
     * @ORM\OneToMany(targetEntity=Image::class, mappedBy="occurrence", cascade={"persist"})
     * @OA\Property(
     *     property="image_id",
     *     type="integer",
     *     example=131269
     * )
     * @Groups({"show_trail", "list_trail", "create_trail"})
     */
    private $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSentier(): ?Sentier
    {
        return $this->sentier;
    }

    public function setSentier(?Sentier $sentier): self
    {
        $this->sentier = $sentier;

        return $this;
    }

    public function getCardTag(): ?string
    {
        return $this->card_tag;
    }

    public function setCardTag(?string $card_tag): self
    {
        $this->card_tag = $card_tag;

        return $this;
    }

    public function getPosition(): ?array
    {
        return $this->position;
    }

    public function setPosition(?array $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getAnecdotes(): ?string
    {
        return $this->anecdotes;
    }

    public function setAnecdotes(?string $anecdotes): self
    {
        $this->anecdotes = $anecdotes;

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

    public function getDateSuppression(): ?\DateTimeInterface
    {
        return $this->date_suppression;
    }

    public function setDateSuppression(?\DateTimeInterface $date_suppression): self
    {
        $this->date_suppression = $date_suppression;

        return $this;
    }

    public function getTaxon(): ?array
    {
        return $this->taxon;
    }

    public function setTaxon(?array $taxon): self
    {
        $this->taxon = $taxon;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setOccurrence($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getOccurrence() === $this) {
                $image->setOccurrence(null);
            }
        }

        return $this;
    }
}
