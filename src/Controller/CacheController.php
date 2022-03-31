<?php

namespace App\Controller;

use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CacheController extends AbstractController
{
    /**
     * @Route("/cache/status")
     */
    public function cacheStatus()
    {
        // cache last refresh date and status
        return $this->json(['status' => 'OK']);
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
        return $this->json($trails->getTrail('Sur les traces des mineurs de Gréasque'));
    }
}
