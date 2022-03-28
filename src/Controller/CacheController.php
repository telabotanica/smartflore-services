<?php

namespace App\Controller;

use App\Service\TrailsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CacheController extends AbstractController
{
    /**
     * @Route("/cache/status")
     */
    public function cacheStatus()
    {
        // cache last refresh ?
        return $this->json(['status' => 'OK']);
    }

    /**
     * @Route("/cache/warmup/{force}", name="cache_warmup", requirements={"force"="force"})
     */
    public function warmupCache(TrailsService $trails, bool $force = false)
    {
        // response: warmed up ok / already fresh / forced refresh / error
        return $this->json($trails->getTrails($force));
    }

    /**
     * @Route("/cache/sentier")
     */
    public function testCache(TrailsService $trails)
    {
        return $this->json($trails->getTrail());
    }
}
