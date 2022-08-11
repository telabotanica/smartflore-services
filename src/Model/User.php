<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class User
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="Pseudo"
     * )
     * @Groups({"user_trail"})
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="\/\/www.gravatar.com\/avatar\/a9b9b8484076540924c03af816c77fc8?s=50&r=g&d=mm"
     * )
     * @Groups({"user_trail"})
     */
    private $avatar;

    /**
     * @var Trail[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Trail::class))
     * )
     * @Groups({"user_trail"})
     */
    private $trails;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): User
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): User
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): User
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * @return Trail[]
     */
    public function getTrails(): array
    {
        return $this->trails;
    }

    /**
     * @param Trail[] $trails
     * @return User
     */
    public function setTrails(array $trails): User
    {
        $this->trails = $trails;
        return $this;
    }
}
