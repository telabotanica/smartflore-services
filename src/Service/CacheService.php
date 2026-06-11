<?php

namespace App\Service;

use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CacheService
{
    public function __construct(private readonly TrailsService $trails, private readonly CacheInterface $cache, private readonly Stopwatch $stopwatch)
    {
    }

    /**
     * Smoke test each trails and each associated resources
     * All in one solution
     *
     * for each trail:
     * get occurrences, their taxon and infos
     * trails -> trail -> occurrence -> taxon -> card
     * @return array of stats
     */
    public function warmup(bool $force): array
    {
        $this->stopwatch->start('warmup-cache');

        $trails = $this->trails->getTrails($force);

        $event = $this->stopwatch->stop('warmup-cache');
        $time = floor($event->getDuration()/1000);

        $statsCache = $this->cache->getItem('stats');
        if (!$force && $statsCache->isHit()) {
            $stats = $statsCache->get();
        } else {
            $stats = [
                'trails' => count($trails),
                'date' => (new DateTime())->format('r'),
                'forced' => $force,
                'time' => $time.'s',
            ];
            $statsCache->set($stats);
            $this->cache->save($statsCache);
        }

        return $stats;
    }

    public function refresh(): array
    {
        return $this->warmup(true);
    }

    public function getStatus(): array
    {
        $cache = $this->cache->getItem('stats');

        return [
            'status' => $cache->isHit() ? 'ok' : 'cold',
            'details' => $cache->isHit() ? $cache->get() : []
        ];
    }
}
