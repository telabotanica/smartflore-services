<?php

namespace App\Model;

class Path
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var Point[]
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
        return $this->coordinates;
    }

    public function setCoordinates(array $coordinates)
    {
        foreach ($coordinates as $coord) {
            $this->coordinates[] = (new Point())->setPosition($coord)->getPosition();
        }

        return $this;
    }
}
