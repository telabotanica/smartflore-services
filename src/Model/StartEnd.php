<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class StartEnd
{
    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.610769, "lng":3.876716}
     * )
     * @Assert\All(
     *     @Assert\Type("float")
     * )
     * @Assert\Count(
     *     min = 2,
     *     max = 2
     * )
     * @Groups({"create_trail"})
     */
    private $start;

    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.610769, "lng":3.876716}
     * )
     * @Assert\All(
     *     @Assert\Type("float")
     * )
     * @Assert\Count(
     *     min = 2,
     *     max = 2
     * )
     * @Groups({"create_trail"})
     */
    private $end;

    public function getStart(): array
    {
        return [
            'lat' => $this->start['lat'],
            'lng' => $this->start['lng'],
        ];
    }

    public function setStart(array $start): self
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd(): array
    {
        return [
            'lat' => $this->end['lat'],
            'lng' => $this->end['lng'],
        ];
    }

    public function setEnd(array $end): self
    {
        $this->end = $end;
        return $this;
    }
}
