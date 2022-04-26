<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CacheService
{
    private $trails;
    private $cards;
    private $cache;
    private $stopwatch;

    public function __construct(
        TrailsService $trails,
        EfloreService $cards,
        CacheInterface $trailsCache,
        Stopwatch $stopwatch
    ) {
        $this->trails = $trails;
        $this->cards = $cards;
        $this->cache = $trailsCache;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Smoke test each cached routes to warmup cache and check for defect
     * All in one solution
     *
     * for each trails :
     * get occurrences, their taxon and infos
     * trails -> trail -> occurrence -> taxon
     * @return array of stats
     */
    public function warmup(bool $force): array
    {
        $this->stopwatch->start('warmup-cache');
        $alreadySeenTaxon = [];
        $trails = $this->trails->getTrails($force);
        foreach ($trails as $trail) {
            $trailName = $this->trails->extractTrailName($trail);
            $trail = $this->trails->getTrail($trailName, $force);

            foreach ($trail->getOccurrences() as $occurrence) {
                $taxon = $occurrence->getTaxo();
                $taxonId = $this->cards->getTaxonInfo($taxon->getReferentiel(), $taxon->getNumNom(), $force)['num_taxonomique'];

                if (!in_array($taxonId, $alreadySeenTaxon)) {
                    $alreadySeenTaxon[] = $taxonId;

                    $this->cards->getCardText($taxon->getReferentiel(), $taxonId, $force);
                    $this->cards->getCardSpeciesImages($taxon->getReferentiel(), $taxon->getNumNom(), $force);
                    $this->cards->getCardCosteImage($taxon->getReferentiel(), $taxonId, $force);
                }

                $this->trails->getTrailSpecieImages($trailName, $taxon->getReferentiel(), $taxonId, $force);
            }
        }

        $event = $this->stopwatch->stop('warmup-cache');
        $time = floor($event->getDuration()/1000);
        dump($event->getDuration()/1000);

        $statsCache = $this->cache->getItem('stats');
        if (!$force && $statsCache->isHit()) {
            $stats = $statsCache->get();
        } else {
            $stats = [
                'trails' => count($trails),
                'date' => (new \DateTime())->format('r'),
                'forced' => $force,
                'time' => $time.'s',
            ];
            $statsCache->set($stats);
            $this->cache->save($statsCache);
        }

        return $stats;
    }

    public function refresh()
    {
        return $this->warmup(true);
    }

    public function getStatus(): array
    {
        $cache = $this->cache->getItem('stats');

        return [
            'status' => 'ok',
            'details' => $cache->isHit() ? $cache->get() : []
        ];
    }
}
