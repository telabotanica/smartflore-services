<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Path
{
    /**
     * @OA\Property(
     *     type="string",
     *     example="LineString"
     * )
     */
    #[Groups(['show_trail', 'list_trail', 'create_trail'])]
    private ?string $type = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="array",
     *         @OA\Items(type="float"),
     *     ),
     *     example={
     *         {"lat":43.610769, "lng":3.876716},
     *         {"lat":43.610769, "lng":3.876716},
     *         {"lat":43.610769, "lng":3.876716},
     *         {"lat":43.610769, "lng":3.876716}
     *     }
     * )
     */
    #[Groups(['show_trail', 'list_trail', 'create_trail'])]
    private ?array $coordinates = null;

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
		if (!empty($this->coordinates)){
			foreach ($this->coordinates as $coordinate) {
				$coordinates[] = (new Point())->setPosition($coordinate)->getPosition();
			}
		}

        return $coordinates;
    }

    public function setCoordinates(array $coordinates): self
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
