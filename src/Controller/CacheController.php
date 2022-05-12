<?php

namespace App\Controller;

use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CacheController extends AbstractController
{
    /**
     * @Route("/cache/status", methods={"GET"})
     */
    public function cacheStatus(CacheService $cache)
    {
        return $this->json($cache->getStatus());
    }

    /**
     * @Route("/cache/warmup/{force}", name="cache_warmup", requirements={"force"="force"}, methods={"GET"})
     */
    public function warmupCache(CacheService $cache, bool $force = false)
    {
        return $this->json($cache->warmup($force));
    }
}
