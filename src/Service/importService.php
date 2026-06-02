<?php

namespace App\Service;

use App\Entity\Favoris;
use App\Entity\Occurrence;
use App\Entity\Path;
use App\Entity\Sentier;
use App\Model\Point;
use App\Model\Taxon;
use App\Service\EfloreService;
use App\Service\CreateTrailService;

class importService
{
    private EfloreService $efloreService;
    private CreateTrailService $createTrailService;
    private SharedService $sharedService;

    public function __construct(EfloreService $efloreService, CreateTrailService $createTrailService, SharedService $sharedService)
    {
        $this->efloreService = $efloreService;
        $this->createTrailService = $createTrailService;
        $this->sharedService = $sharedService;
    }

    public function creerSentier(array $trail, Sentier $sentier, array $sentierCoords = null): Sentier
    {
        $sentier->setAuthorId("0");
        $sentier->setNom($trail['nom']);
        $sentier->setAncienId($trail['id']);
        $sentier->setDisplayName($trail['nom']);
        $sentier->setAuteur($trail['auteur'] ?? null);
        $sentier->setAuteurEmail($trail['auteur_email'] ?? null);
        $sentier->setStatus($trail['statut'] ?? null);
        $sentier->setPmr(is_numeric($trail['pmr']) ? (int)$trail['pmr'] : -1);
        $sentier->setMeilleuresSaisons(json_decode($trail['meilleures_saisons'] ?? '[]', true));

        if ($sentierCoords) {
            $sentier->setPosition([
                'start' => $sentierCoords,
                'end' => $sentierCoords,
            ]);
        }

        if (is_numeric($trail['date_creation'])) {
            $sentier->setDateCreation((new \DateTime())->setTimestamp((int)$trail['date_creation']));
        } else {
            $sentier->setDateCreation(new \DateTime('01-01-1970'));
        }
        if (is_numeric($trail['date_modification'])) {
            $sentier->setDateModification((new \DateTime())->setTimestamp((int)$trail['date_modification']));

            if ($sentier->getStatus() == 'Validé'){
                $sentier->setDatePublication((new \DateTime())->setTimestamp((int)$trail['date_modification']));
            }
        }
        if (is_numeric($trail['date_suppression'])) {
            $sentier->setDateSuppression((new \DateTime())->setTimestamp((int)$trail['date_suppression']));
        }



        return $sentier;
    }

    public function fusionneFichesAvecEtSansLocalisation(array $individus, array $individusWithPosition): array
    {
        $individusFusionnes = [];

        foreach ($individus as $individu) {
            $positions = array_filter(
                $individusWithPosition,
                fn($item) => ($item['ficheTag'] ?? null) === $individu
            );

            if (count($positions) > 0) {
                foreach ($positions as $pos) {
                    $individusFusionnes[] = [
                        'ficheTag' => $individu,
                        'lat' => $pos['lat'] ?? null,
                        'lng' => $pos['lng'] ?? null,
                    ];
                }
            } else {
                $individusFusionnes[] = [
                    'ficheTag' => $individu,
                    'lat' => null,
                    'lng' => null,
                ];
            }
        }
        return $individusFusionnes;
    }

    public function ajouterCheminAuSentier(array $trail, Sentier $sentier): Sentier
    {
        $dessin = json_decode($trail['dessin'] ?? '', true);
        if (isset($dessin['coordinates']) && !empty($dessin['coordinates'])) {
            $coordinates = [];
            $path = new Path();
            $path->setType($dessin['type'] ?? 'LineString');
            foreach ($dessin['coordinates'] as $coordinate) {
                $coordinates[] = (new Point())->setPosition($coordinate)->getPosition();
            }
            $path->setCoordinates($coordinates);
            $sentier->setChemin($path);
        }
        $sentier->setPathLength(round(TrailsService::getTrailLength($sentier)));
        return $sentier;
    }

    public function addTaxonToOccurrence(Occurrence $occurrence, array $individu, array $trail, array $taxons)
    {
        $tabs = array_column($taxons, 'tabs');
        $index = array_search($individu['ficheTag'],$tabs);
        if ($index !== false) {
            $occurrence->setTaxon($taxons[$index]);
            return $taxons;
        }

        $taxonFromFiche = $this->sharedService->digestIndividuId($individu['ficheTag']);

        if (isset($taxonFromFiche[0]) && isset($taxonFromFiche[1])) {
            $referentiel = $taxonFromFiche[0];
            $num_taxonomique = $taxonFromFiche[1];
            try {
                $infos = $this->efloreService->getInfosTaxons($referentiel, $num_taxonomique);
                $taxon = $this->mapTaxonInfos($infos, $referentiel);

                $occurrence->setTaxon([
                    'scientific_name' => $taxon->getFullScientificName(),
                    'taxon_repository' => $taxon->getReferentiel(),
                    'name_id' => $taxon->getNumNom(),
                    'taxonomic_id' => $taxon->getTaxonomicId()
                ]);
                // Besoin de $occurrence->getTaxon()['taxon_repository'] et $occurrence->getTaxon()['name_id']
                $this->createTrailService->getCardTag($occurrence);
                $taxons[] = $occurrence->getTaxon();
            } catch (\Exception $e) {
//                echo (" || ". $trail['nom'] . ": ". $referentiel ." ". $num_taxonomique ." ". $e->getMessage()) ;
            }
        }

        return $taxons;
    }

    public function findUserFavoris(array $user, array $taxons, array $userFavoris): array
    {
        $favoris = new Favoris();
        $tabs = array_column($taxons, 'tabs');
        $index = array_search($userFavoris['value'], $tabs);
        if ($index !== false) {
            $taxon = $taxons[$index];
            $favoris->setScientificName($taxon['scientific_name']);
            $favoris->setTaxonId($taxon['name_id']);
            $favoris->setReferentiel($taxon['taxon_repository']);
        } else {
            $taxon = $this->findTaxonInfoForFavorite($userFavoris['value']);
            $favoris->setScientificName($taxon->getFullScientificName());
            $favoris->setTaxonId($taxon->getNumNom());
            $favoris->setReferentiel($taxon->getReferentiel());
            $taxons[] = [
                'scientific_name' => $taxon->getFullScientificName(),
                'taxon_repository' => $taxon->getReferentiel(),
                'name_id' => $taxon->getNumNom()
            ];
        }

        $favoris->setUserId($user['id']);
        $favoris->setUserEmail($user['email']);

        return [$taxons, $favoris];
    }

    public function findTaxonInfoForFavorite(string $ficheTag): Taxon
    {
        $taxon = new Taxon();
        $taxonFromFiche = $this->sharedService->digestIndividuId($ficheTag);
        if (isset($taxonFromFiche[0]) && isset($taxonFromFiche[1])) {
            $referentiel = $taxonFromFiche[0];
            $num_taxonomique = $taxonFromFiche[1];
            try {
                $infos = $this->efloreService->getInfosTaxons($referentiel, $num_taxonomique);
                $taxon = $this->mapTaxonInfos($infos, $referentiel);
            }  catch (\Exception $e) {
                echo (" || ". "erreur lors de la récupération du taxon" . ": ". $referentiel ." ". $num_taxonomique ." ". $e->getMessage()) ;
            }
        }

        return $taxon;
    }

    public function mapTaxonInfos(array $infos, string $referentiel): Taxon
    {
        $keys = array_keys($infos['resultat']);
        $num_nom = array_pop($keys);

        $taxon = new Taxon();
        $taxon->setFullScientificName($infos['resultat'][$num_nom]['nom_sci'] ?? "");
        $taxon->setReferentiel($referentiel ?? "");
        $taxon->setNumNom($num_nom ?? 0);
        $taxon->setTaxonomicId($infos['resultat'][$num_nom]['num_taxonomique'] ?? null);

        return $taxon;
    }

    public function addImagesToOccurrence(array $trail, array $individu, Occurrence $occurrence)
    {
        if ($trail['images']) {
            foreach (json_decode($trail['images']) as $key => $value) { //$key=fichetag, $value=array avec images id
                if (!is_array($value)) { // Cas où l'image est une simple string
                    $value = [$value];
                }
                foreach ($value as $image) {
                    if ($individu['ficheTag'] == $key) {
                        $this->createTrailService->setImagesToOccurrence($occurrence, $image);
                    }
                }
            }
        }
    }
}