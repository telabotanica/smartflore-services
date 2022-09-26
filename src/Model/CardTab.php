<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class CardTab
{
    public const CARD_TAB_TYPES = ['card', 'gallery', 'webview'];
    public const CARD_TAB_ICONS = ['card', 'gallery', 'map', 'wikipedia', 'form', 'webview', 'default'];

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     description="Card, Gallery, Map, Wikipedia, etc.",
     *     example="Form"
     * )
     * @Groups({"show_taxon"})
     */
    private $title;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     description="card, gallery, webview, etc.",
     *     example="webview"
     * )
     * @Groups({"show_taxon"})
     */
    private $type;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     description="tab's icon: card, gallery, map, wikipedia, form, default, webview, etc.",
     *     example="map"
     * )
     * @Groups({"show_taxon"})
     */
    private $icon;

    /**
     * @var CardSection[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=CardSection::class))
     * )
     * @Groups({"show_taxon"})
     */
    private $sections;

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
     * @var string
     * @OA\Property(
     *     type="string",
     *     description="Webview URL",
     *     example="https://fr.wikipedia.org/wiki/Acer_campestre"
     * )
     * @Groups({"show_taxon"})
     */
    private $url;

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return CardTab
     */
    public function setTitle(string $title): CardTab
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return CardTab
     */
    public function setType(string $type): CardTab
    {
        if (!in_array($type, self::CARD_TAB_TYPES)) {
            throw new \InvalidArgumentException('Given card type :"'.$type.'" is not allowed');
        }

        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon ?? 'default';
    }

    /**
     * @param string $icon
     * @return CardTab
     */
    public function setIcon(string $icon): CardTab
    {
        if (!in_array($icon, self::CARD_TAB_ICONS)) {
            throw new \InvalidArgumentException('Given icon :"'.$icon.'" is not allowed');
        }

        $this->icon = $icon;
        return $this;
    }

    /**
     * @return CardSection[]
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * @param CardSection[] $sections
     * @return CardTab
     */
    public function setSections(array $sections): CardTab
    {
        $this->sections = $sections;
        return $this;
    }

    public function addSection(string $title, string $text): CardTab
    {
        $section = new CardSection();
        $section->setTitle($title)
            ->setText($text);

        $this->sections[] = $section;

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
     * @return CardTab
     */
    public function setImages(array $images): CardTab
    {
        $this->images = $images;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return CardTab
     */
    public function setUrl(string $url): CardTab
    {
        $this->url = $url;
        return $this;
    }
}
