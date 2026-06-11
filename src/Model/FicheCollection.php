<?php

namespace App\Model;

use App\Model\Entete;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

class FicheCollection
{
    /**
     * @var Entete
     * @OA\Property(ref=@Model(type=Entete::class))
     */
    #[Assert\Type(Entete::class)]
    #[Groups(['list_fiche'])]
    private Entete $entete;

    /**
     * @var FicheResultats[] | null $resultats
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=FicheResultats::class, groups={"list_fiche"}))
     * )
     * @Assert\All(
     *     @Assert\Type(FicheResultats::class)
     * )
     */
    #[Groups(['list_fiche'])]
    private ?array $resultats = null;

    /**
     * @return Entete
     */
    public function getEntete(): Entete
    {
        return $this->entete;
    }

    /**
     * @param Entete $entete
     */
    public function setEntete(Entete $entete): void
    {
        $this->entete = $entete;
    }

    /**
     * @return FicheResultats[]|null
     */
    public function getResultats(): ?array
    {
        return $this->resultats;
    }

    /**
     * @param FicheResultats[]|null $resultats
     */
    public function setResultats(?array $resultats): void
    {
        $this->resultats = $resultats;
    }



}