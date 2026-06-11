<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Image
{
    public function __construct(
        /**
         * @OA\Property(
         *     type="int",
         *     example="131269"
         * )
         */
        #[Groups(['show_trail', 'list_trail', 'show_taxon', 'user_trail'])]
        private int $id,
        /**
         * @OA\Property(
         *     type="string",
         *     example="https://api.tela-botanica.org/img:002221908O"
         * )
         */
        #[Groups(['show_trail', 'list_trail', 'show_taxon', 'user_trail'])]
        private string $url,
        /**
         * @OA\Property(
         *     type="string",
         *     example="Jean Michel Photographe"
         * )
         */
        #[Groups(['show_trail', 'list_trail', 'show_taxon', 'user_trail'])]
        private string $author,
        /**
         * @OA\Property(
         *     type="string",
         *     example="https://api.tela-botanica.org/img:002221908CXS"
         * )
         */
        #[Groups(['show_trail', 'list_trail', 'user_trail', 'show_taxon', 'user_trail', 'create_trail', 'update_occurrence'])]
        private ?string $mini = null
    )
    {
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
