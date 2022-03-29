<?php

namespace App\Controller;

use App\Service\TrailsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TrailController extends AbstractController
{
    /**
     * @Route("/sentiers", name="sentier_list")
     */
    public function sentiers(TrailsService $trails)
    {
        return $this->json($trails->getTrails());
    }

    /**
     * @Route("/sentiers/{name}", name="sentier_details")
     */
    public function sentier(TrailsService $trails, string $name)
    {
        return $this->json($trails->getTrail($name));
    }
}
