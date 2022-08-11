<?php

namespace App\Controller;

use App\Model\Trail;
use App\Service\AnnuaireService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     *         @OA\Items(ref=@Model(type=Trail::class, groups={"user_trail"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="token",
     *     in="query",
     *     description="Token",
     *     example="thisisatokenlol",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Login")
     * @Route("/me", name="user_trail", methods={"GET"})
     */
    public function me(AnnuaireService $annuaire, SerializerInterface $serializer, Request $request)
    {
        $token = $request->query->get('token', '');
        $cookie = null;
        // cookie is optional, used only if token is expired
        // get full cookie values is strange :
        // get(tb_auth) retrieve only cookie[tb_auth] instead of full cookie info
        // so, we need to check if cookie is set to get all its props
        // I wonder how it behaves with multiples cookies, but it shouldn't happen
        if ($request->cookies->get($annuaire->getCookieName())) {
            $cookie = $request->cookies->all();
        }

        if (!trim($token)) {
            throw new BadRequestHttpException('Token is empty');
        }

        $user = $annuaire->getUser($token, $cookie);
        if (is_string($user)) {
            // if it's a string, then it's an error (yes, could be handled better)
            $json = json_encode($user);
        } else {
            $json = $serializer->serialize($user, 'json', ['groups' => 'user_trail']);
        }

        return new JsonResponse($json, 200, [], true);
    }
}
