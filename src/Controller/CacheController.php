<?php

namespace App\Controller;

use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CacheController extends AbstractController
{
    /**
     * @var \App\Service\CacheService
     */
    private $cache;
    public function __construct(\App\Service\CacheService $cache)
    {
        $this->cache = $cache;
    }
    /**
     * @Route("/cache/status", methods={"GET"})
     */
    public function cacheStatus(): \Symfony\Component\HttpFoundation\JsonResponse
    {
        return $this->json($this->cache->getStatus());
    }

    /**
     * @Route("/cache/warmup/{force}", name="cache_warmup", requirements={"force"="force"}, methods={"GET"})
     */
    public function warmupCache(bool $force = false): \Symfony\Component\HttpFoundation\JsonResponse
    {
        set_time_limit(0);

        return $this->json($this->cache->warmup($force));
    }
}
