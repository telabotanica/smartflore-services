<?php

namespace App\Model;

class Path
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $coordinates;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Path
    {
        $this->type = $type;
        return $this;
    }

    public function getCoordinates(): array
    {
        $coordinates = [];
        foreach ($this->coordinates as $coordinate) {
            $coordinates[] = (new Point())->setPosition($coordinate)->getPosition();
        }

        return $coordinates;
    }

    public function setCoordinates(array $coordinates)
    {
        $this->coordinates = $coordinates;

        return $this;
    }
}
