<?php

namespace App\Model;

class Point
{
    /**
     * @var float[]
     */
    private $position;

    public function getPosition(): array
    {
        return [
            'lat' => $this->position[1],
            'lng' => $this->position[0],
        ];
    }

    public function setPosition(array $position): self
    {
        $this->position = $position;
        return $this;
    }
}
