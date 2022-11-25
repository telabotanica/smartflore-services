<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Path
{
    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="LineString"
     * )
     * @Groups({"show_trail", "list_trail", "create_trail"})
     */
    private $type;

    /**
     * @var array
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="array",
     *         @OA\Items(type="float"),
     *     ),
     *     example={
     *         {"lat":43.610769, "lon":3.876716},
     *         {"lat":43.610769, "lon":3.876716},
     *         {"lat":43.610769, "lon":3.876716},
     *         {"lat":43.610769, "lon":3.876716}
     *     }
     * )
     * @Groups({"show_trail", "list_trail", "create_trail"})
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

    public function getGeoJson(): array
    {
        return [
            'type' => $this->getType(),
            'coordinates' => $this->coordinates
        ];
    }
}
