<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Point
{
    /**
     * @var float[] | null
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.610769, "lng":3.876716}
     * )
     * @Groups({"create_trail"})
     */
    private $position;

    public function getPosition(): ?array
    {
		if (isset($this->position[1])){
			return [
				'lat' => $this->position[1],
				'lng' => $this->position[0],
			];
		} elseif (isset($this->position['lon'])) {
			return [
				'lat' => $this->position['lat'],
				'lng' => $this->position['lon'],
			];
		} elseif (isset($this->position['lng'])) {
			return [
				'lat' => $this->position['lat'],
				'lng' => $this->position['lng'],
			];
		} else {
			return null;
		}
    
    }

    public function setPosition(array $position): self
    {
        $this->position = $position;
        return $this;
    }
}
