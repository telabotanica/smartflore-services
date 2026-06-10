<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CacheController extends AbstractController
{
    public function __construct(private readonly CacheService $cache)
    {
    }
    #[Route(path: '/cache/status', methods: ['GET'])]
    public function cacheStatus(): JsonResponse
    {
        return $this->json($this->cache->getStatus());
    }

    #[Route(path: '/cache/warmup/{force}', name: 'cache_warmup', requirements: ['force' => 'force'], methods: ['GET'])]
    public function warmupCache(bool $force = false): JsonResponse
    {
        set_time_limit(0);

        return $this->json($this->cache->warmup($force));
    }
}
