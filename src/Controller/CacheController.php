<?php

namespace App\Controller;

use App\Service\SentiersService;
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
    public function warmupCache(SentiersService $sentiers)
    {
        return $this->json($sentiers->getSentiers());
    }
}
