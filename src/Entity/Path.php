<?php

namespace App\Entity;

use App\Model\Point;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

#[ORM\Entity]
class Path
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @OA\Property(type="string", example="LineString")
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['show_trail', 'list_trail', 'create_trail', 'update_trail', 'user_trail'])]
    private ?string $type = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="array", @OA\Items(type="float")),
     *     example={
     *         {"lat":43.610769, "lng":3.876716},
     *         {"lat":43.610771, "lng":3.876718}
     *     }
     * )
     */
    #[ORM\Column(type: 'json')]
    #[Groups(['show_trail', 'list_trail', 'create_trail', 'update_trail', 'user_trail'])]
    private array $coordinates = [];

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
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
