<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;

class CacheService
{
    public $trails;
    public $cards;
    public $cache;

    public function __construct(
        TrailsService $trails,
        EfloreService $cards,
        CacheInterface $trailsCache
    ) {
        $this->trails = $trails;
        $this->cards = $cards;
        $this->cache = $trailsCache;
    }

    /**
     * Smoke test each cached routes to warmup cache and check for defect
     * All in one solution
     *
     * for each trails :
     * get occurrences, their taxon and infos
     * trails -> trail -> occurrence -> taxon
     * @return array of stats ?
     */
    public function warmup(bool $force)
    {
        $alreadySeenTaxon = [];
        $trails = $this->trails->getTrails($force);
        foreach ($trails as $trail) {
            $trail = $this->trails->getTrail($trail->getNom(), $force);
//            dump($trail);

            foreach ($trail->getOccurrences() as $occurrence) {
                dump($occurrence);
                $taxon = $occurrence->getTaxon();
                $taxonId = $this->cards->getTaxonInfo($taxon->getReferential(), $taxon->getNumNom(), $force)['num_taxonomique'];

                if (!in_array($taxonId, $alreadySeenTaxon)) {
                    $alreadySeenTaxon[] = $taxonId;

                    $this->cards->getCardText($taxon->getReferential(), $taxonId, $force);
                    $this->cards->getCardSpeciesImages($taxon->getReferential(), $taxon->getNumNom(), $force);
                    $this->cards->getCardCosteImage($taxon->getReferential(), $taxonId, $force);
                }

                $this->trails->getTrailSpecieImages($trail->getNom(), $taxon->getReferential(), $taxonId, $force);
            }
        }

        $stats = [
            'trails' => count($trails),
            'date' => (new \DateTime())->format('r'),
            'forced' => $force,
        ];
        $statsCache = $this->cache->getItem('stats');
        $statsCache->set($stats);
        $this->cache->save($statsCache);

        // execution time, counts, errors, etc.
        return $stats;
    }

    public function refresh()
    {
        return $this->warmup(true);
    }
}
