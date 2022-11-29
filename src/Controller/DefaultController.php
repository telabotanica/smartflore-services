<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class DefaultController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="A static web page",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string", example="my page")
     *     )
     * )
     * @OA\Tag(name="Static")
     * @Route("/about", name="aboutmz", methods={"GET"})
     */
    public function about(): Response
    {
        return $this->render('statics/about.html.twig');
    }

    /**
     *  * @OA\Response (
     *     response="200",
     *     description="A static web page",
     * )
     * @OA\Property(description="This is my coworker!")
     * @OA\Tag(name="Static")
     * @Route("/credits", name="credits", methods={"GET"})
     */
    public function credits(): Response
    {
        return $this->render('statics/credits.html.twig');
    }

    /**
     *  * @OA\Response (
     *     response="200",
     *     description="A static web page",
     * )
     * @OA\Tag(name="Static")
     * @Route("/terms_of_use", name="terms_of_use", methods={"GET"})
     */
    public function termsOfUse(): Response
    {
        return $this->render('statics/terms_of_use.html.twig');
    }
}
