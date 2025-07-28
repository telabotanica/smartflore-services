<?php

namespace App\Controller;

use App\Model\User;
use App\Service\AnnuaireService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MeController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="Get user info and trails",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=User::class, groups={"user_trail"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="token",
     *     in="query",
     *     description="Token (optional)",
     *     example="thisisatokenlol",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Login")
     * @Route("/me", name="user_trail", methods={"GET"})
     */
    public function me(AnnuaireService $annuaire, SerializerInterface $serializer, Request $request): Response
    {
        try {
            $token = $annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }

            $cookie = $request->cookies->get($annuaire->getCookieName()) ?? null;
            $cookie = [
                $annuaire->getCookieName() => $token
            ];
            $user = $annuaire->getUser($token, $cookie);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification sur la route /me: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        if (is_string($user)) {
            // if it's a string, then it's an error (yes, could be handled better)
            $json = json_encode($user);
        } else {
            $json = $serializer->serialize($user, 'json', ['groups' => 'user_trail']);
        }

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
