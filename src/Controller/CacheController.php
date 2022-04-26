<?php

namespace App\Controller;

use App\Service\CacheService;
use App\Service\TrailsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CacheController extends AbstractController
{
    /**
     * @Route("/cache/status")
     */
    public function cacheStatus(CacheService $cacheService)
    {
        return $this->json($cacheService->getStatus());
    }

    /**
     * @Route("/cache/warmup/{force}", name="cache_warmup", requirements={"force"="force"})
     */
    public function warmupCache(CacheService $cache, bool $force = false)
    {
        // response: warmed up ok / already fresh / forced refresh / error
        return $this->json($cache->warmup($force));
    }

    /**
     * @Route("/cache/sentier")
     */
    public function testCache(TrailsService $trails)
    {
        return $this->json($trails->getTrail('Sur les traces des mineurs de Gréasque')->getOccurrences());
    }

    /**
     * @Route("/cache/sentiers")
     */
    public function test2Cache(TrailsService $trails)
    {
//        dump($trails->getTrail('Sur les traces des mineurs de Gréasque')->getOccurrences());die;
        dump($trails->getTrail('REVE', true)->getOccurrences()[0]->getTaxo()->getReferentiel());die;
        return $this->json($trails->getTrail('Sur les traces des mineurs de Gréasque')->getOccurrences());
    }
}
