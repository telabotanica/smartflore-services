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
        return $this->json(['status' => 'OK']);
    }

    /**
     * @Route("/cache/warmup")
     */
    public function warmupCache(TrailsService $trails)
    {
        return $this->json($trails->getTrails());
    }

    /**
     * @Route("/cache/sentier")
     */
    public function testCache(TrailsService $trails)
    {
        return $this->json($trails->getTrail());
    }
}
