<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class CardSection
{
    /**
     * @OA\Property(
     *     type="string",
     *     example="Description"
     * )
     */
    #[Groups(['show_taxon'])]
    private ?string $title = null;

    /**
     * @OA\Property(
     *     type="string",
     *     example="This is a description"
     * )
     */
    #[Groups(['show_taxon'])]
    private ?string $text = null;

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return CardSection
     */
    public function setTitle(string $title): CardSection
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return CardSection
     */
    public function setText(string $text): CardSection
    {
        $this->text = $text;
        return $this;
    }
}
