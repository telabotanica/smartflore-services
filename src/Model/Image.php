<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Image
{
    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="131269"
     * )
     * @Groups({"show_trail", "list_trail", "show_taxon", "user_trail"})
     */
    private $id;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="https://api.tela-botanica.org/img:002221908O"
     * )
     * @Groups({"show_trail", "list_trail", "show_taxon", "user_trail"})
     */
    private $url;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Jean Michel Photographe"
     * )
     * @Groups({"show_trail", "list_trail", "show_taxon", "user_trail"})
     */
    private $author;

    /**
     * @var string|null
     * @OA\Property(
     *     type="string",
     *     example="https://api.tela-botanica.org/img:002221908CXS"
     * )
     * @Groups({"show_trail", "list_trail", "user_trail", "show_taxon", "user_trail", "create_trail", "update_occurrence"})
     */
    private $mini;

    public function __construct(
        int $id,
        string $url,
        string $author,
        ?string $mini = null
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->author = $author;
        $this->mini = $mini;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Image
     */
    public function setId(int $id): Image
    {
        $this->id = $id;
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
     * @return Image
     */
    public function setUrl(string $url): Image
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return Image
     */
    public function setAuthor(string $author): Image
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return ?string
     */
    public function getMini(): ?string
    {
        return $this->mini;
    }

    /**
     * @param string $mini
     * @return Image
     */
    public function setMini(?string $mini): Image
    {
        $this->mini = $mini;
        return $this;
    }


}
