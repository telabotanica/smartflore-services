<?php

namespace App\Model;

use App\Service\TrailsService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Photo
{
    /**
     * @var int|string
     * @OA\Property(
     *     type="int",
     *     example="146"
     * )
     */
    private $id;

    /**
     * @var string
     */
    private $imageNom;

//    /**
//     * @var File
//     * @Assert\NotBlank(message="Please upload a picture")
//     * @Assert\File(mimeTypes="image/JPEG")
//     * @OA\MediaType(
//     *          mediaType="multipart/form-data",
//     *          @OA\Schema(
//     *              @OA\Property(
//     *                  property="photo",
//     *                  description="Picture to upload (format JPG)",
//     *                  type="file"
//     *              ),
//     *          ),
//     *      )
//     * @Groups({"create_photo", "user_photo"})
//     */
//    private $uploadedFile;

    /**
     * @var string[]|int[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="string"),
     *     example={"scientific_name":"Acer campestre", "name_id": 141, "taxon_repository": "bdtfx"}
     * )
     * @Assert\All(
     *  @Assert\NotBlank
     * )
     * @Groups({"create_photo", "user_photo"})
     */
    private $taxon;

    /**
     * @var float[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="float"),
     *     example={"lat":43.6082423, "lon":3.8800137}
     * )
     * @Assert\All(
     *  @Assert\NotBlank
     * )
     * @Groups({"create_photo", "user_photo"})
     */
    private $position;

    /**
     * @var string
     * @OA\Property(
     *     type="string",
     *     example="10/01/2023"
     * )
     * @Assert\NotBlank
     * @Groups({"create_photo", "user_photo"})
     */
    private $date;

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getImageNom(): string
    {
        return $this->imageNom;
    }

    /**
     * @param string $imageNom
     */
    public function setImageNom(string $imageNom): void
    {
        $this->imageNom = $imageNom;
    }

    /**
     * @return string[]|int[]
     */
    public function getTaxon(): array
    {
        return $this->taxon;
    }

    /**
     * @param string[]|int[] $taxon
     */
    public function setTaxon(array $taxon): void
    {
        $this->taxon = $taxon;
    }

    /**
     * @return float[]
     */
    public function getPosition(): array
    {
        return $this->position;
    }

    /**
     * @param float[] $position
     */
    public function setPosition(array $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }
//
//    /**
//     * @return File
//     */
//    public function getUploadedFile(): File
//    {
//        return $this->uploadedFile;
//    }
//
//    /**
//     * @param File $uploadedFile
//     */
//    public function setUploadedFile(File $uploadedFile): void
//    {
//        $this->uploadedFile = $uploadedFile;
//    }



}
