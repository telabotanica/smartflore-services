<?php

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class Image
{
    /**
     * @var int
     * @Groups ({"show_trail", "list_trail"})
     */
    private $id;

    /**
     * @var string
     * @Groups ({"show_trail", "list_trail"})
     */
    private $url;

    public function __construct(int $id, string $url)
    {
        $this->id = $id;
        $this->url = $url;
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
}
