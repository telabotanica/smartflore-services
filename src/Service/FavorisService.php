<?php

namespace App\Service;

use App\Entity\Favoris;
use Doctrine\ORM\EntityManagerInterface;

class FavorisService
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    public function checkExistingFavoris(string $referentiel, int $taxonId, string $userId): bool
    {
        $existingFavoris = $this->em->getRepository(Favoris::class)->findOneBy(['taxon_id' => $taxonId, 'referentiel' => $referentiel, 'user_id' => $userId]);

        return (bool)$existingFavoris;
    }
}