<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Point
{
    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.610769, "lon":3.876716}
     * )
     * @Groups({"create_trail"})
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
