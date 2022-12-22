<?php

namespace App\Controller;

use App\Model\Favorite;
use App\Service\AnnuaireService;
use App\Service\CreateTrailService;
use App\Service\FavorisService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class FavorisController extends AbstractController
{
    /**
     * @OA\Response(
     *     response="200",
     *     description="Get user favorite species",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Favorite::class, groups={"list_favorite"}))
     *     )
     * )
     * @OA\Tag(name="Favoris")
     * @Route("/favoris", name="user_favorite", methods={"GET"})
     */
    public function getFavoris(SerializerInterface $serializer, Request $request, FavorisService $favoris, CreateTrailService $createTrail, AnnuaireService $annuaire): Response
    {

        $token = $request->headers->get('Authorization');
        $cookie = null;

        if ($request->cookies->get($annuaire->getCookieName())) {
            $cookie = $request->cookies->all();
        }

        if (!trim($token)) {
            throw new BadRequestHttpException('Token is empty');
        }

        $tokenInfos = $annuaire->decodeToken($token);

        $list = $favoris->getFavorisList($token, $tokenInfos);

        $json = $serializer->serialize($list, 'json', ['groups' => 'list_favorite']);

        return new JsonResponse($json, 200, [], true);
    }
}
