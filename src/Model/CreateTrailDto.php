<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CreateTrailDto
{
    // PRM access
    const PRM_VALUES = [
        -1, // don't know
        0,  // not accessible
        1   // accessible
    ];

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Arbres Remarquables"
     * )
     * @Assert\NotBlank
     * @Assert\Length(max={255})
     * @Groups({"create_trail"})
     */
    private $name;

    /**
     * @var StartEnd
     * @OA\Property(ref=@Model(type=StartEnd::class))
     * @Assert\Type(StartEnd::class)
     * @Groups({"create_trail"})
     */
    private $position;

    /**
     * @var CreateOccurrenceDto[] $occurrences
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=CreateOccurrenceDto::class, groups={"create_trail"}))
     * )
     * @Assert\All(
     *     @Assert\Type(CreateOccurrenceDto::class)
     * )
     * @Groups({"create_trail"})
     */
    private $occurrences;

    /**
     * @var Path
     * @OA\Property(ref=@Model(type=Path::class))
     * @Assert\Type(Path::class)
     * @Groups({"create_trail"})
     */
    private $path;

    /**
     * @var int
     * @OA\Property(
     *     type="int",
     *     example="-1"
     * )
     * @Assert\Type("int")
     * @Assert\Range(
     *     min = -1,
     *     max = 1
     * )
     * @Groups({"create_trail"})
     * values : // -1 = don't know // 0 = no // 1 = yes
     */
    private $prm;

    /**
     * @var bool[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="bool"),
     *     example={false, false, false, false}
     * )
     * @Assert\All(
     *     @Assert\Type("bool")
     * )
     * @Assert\Count(
     *     min = 4,
     *     max = 4
     * )
     * @Groups({"create_trail"})
     * Which seasons are the best to visit this sentier?
     * 4 booleans list, one for each season. First is spring, then summer, etc.
     */
    private $bestSeason;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return CreateTrailDto
     */
    public function setName(string $name): CreateTrailDto
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return StartEnd
     */
    public function getPosition(): StartEnd
    {
        return $this->position;
    }

    /**
     * @param StartEnd $position
     * @return CreateTrailDto
     */
    public function setPosition(StartEnd $position): CreateTrailDto
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Occurrence[]|null
     */
    public function getOccurrences(): ?array
    {
        return $this->occurrences;
    }

    /**
     * @param Occurrence[]|null $occurrences
     * @return CreateTrailDto
     */
    public function setOccurrences(?array $occurrences): CreateTrailDto
    {
        $this->occurrences = $occurrences;
        return $this;
    }

    /**
     * @return Path
     */
    public function getPath(): Path
    {
        return $this->path;
    }

    /**
     * @param Path $path
     * @return CreateTrailDto
     */
    public function setPath(Path $path): CreateTrailDto
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return int
     */
    public function getPrm(): int
    {
        return $this->prm;
    }

    /**
     * @param int $prm
     * @return CreateTrailDto
     */
    public function setPrm(int $prm): CreateTrailDto
    {
        if (!in_array($prm, self::PRM_VALUES)) {
            throw new \InvalidArgumentException(
                "Given PRM value : $prm is not in allowed range : ".implode(', ', self::PRM_VALUES));
        }
        $this->prm = $prm;
        return $this;
    }

    /**
     * @return array
     */
    public function getBestSeason(): array
    {
        return $this->bestSeason;
    }

    /**
     * @param array $bestSeason
     * @return CreateTrailDto
     */
    public function setBestSeason(array $bestSeason): CreateTrailDto
    {
        if (count($bestSeason) !== 4) {
            throw new \InvalidArgumentException(
                'Best season array should contain exactly 4 elements instead of given '.count($bestSeason));
        }
        foreach ($bestSeason as $season) {
            if (!is_bool($season)) {
                throw new \InvalidArgumentException(
                    'Best season array should contain only boolean instead of given '.gettype($season));
            }
        }
        $this->bestSeason = $bestSeason;
        return $this;
    }
}
