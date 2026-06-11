<?php

namespace App\Service;

use App\Entity\Favoris;
use Doctrine\ORM\EntityManagerInterface;

class FavorisService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function checkExistingFavoris(string $referentiel, int $taxonId, string $userId): bool
    {
        $existingFavoris = $this->em->getRepository(Favoris::class)->findOneBy(['taxon_id' => $taxonId, 'referentiel' => $referentiel, 'user_id' => $userId]);

        return (bool)$existingFavoris;
    }
}