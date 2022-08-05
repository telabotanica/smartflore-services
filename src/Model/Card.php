<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Card
{
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
     * @var array
     * @OA\Property(
     *     type="array",
     *     description="Card tabs title and type (Card, Gallery, Map, Wikipedia, etc.)",
     *     @OA\Items(
     *         @OA\Property(property="title", type="string", example="Map"),
     *         @OA\Property(property="type", type="string", example="webview"),
     *     )
     * )
     * @Groups({"show_taxon"})
     */
    private $tabs;

    /**
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * @param array $sections
     * @return Card
     */
    public function setSections(array $sections): Card
    {
        $this->sections = $sections;
        return $this;
    }

    public function addSection(string $title, string $text)
    {
        $section = new CardSection();
        $section->setTitle($title)
            ->setText($text);

        $this->sections[] = $section;
    }

    /**
     * @return array
     */
    public function getTabs(): array
    {
        return [
            [
                'title' => 'Card',
                'type' => 'card',
            ], [
                'title' => 'Gallery',
                'type' => 'gallery',
            ], [
                'title' => 'Map',
                'type' => 'webview',
            ], [
                'title' => 'Wikipedia',
                'type' => 'webview',
            ]
        ];
    }

    /**
     * @param array $tabs
     * @return Card
     */
    public function setTabs(array $tabs): Card
    {
        $this->tabs = $tabs;
        return $this;
    }
}
