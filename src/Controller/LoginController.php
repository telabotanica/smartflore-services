<?php

namespace App\Controller;

use App\Service\AnnuaireService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="A token (in body) and a cookie (in headers)",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string", example="thisisatokenlol")
     *     )
     * )
     * @OA\Parameter(
     *     name="login",
     *     in="query",
     *     description="Login (email address)",
     *     example="login@example.org",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="password",
     *     in="query",
     *     description="Password",
     *     example="Pa$$W0rd!",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Login")
     * @Route("/login", methods={"POST"})
     */
    public function login(AnnuaireService $annuaire, Request $request)
    {
        $login = $request->query->get('login', '');
        $password = $request->query->get('password', '');

        if (!trim($login) || !trim($password)) {
            throw new BadRequestHttpException('Login or Password are empty');
        }

        ['token' => $token, 'cookie' => $cookie, 'error' => $error] = $annuaire->getToken($login, $password);

        $response = new JsonResponse($error ?? $token);

        if ($cookie) {
            $response->headers->setCookie(
                Cookie::fromString((string) $cookie)
            );
        }

        return $response;
    }

    /**
     * @OA\Response (
     *     response="200",
     *     description="Refreshed token: don't throw away your old token! Give it to us and get a new one :)",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string", example="thisisatokenlol")
     *     )
     * )
     * @OA\Parameter(
     *     name="token",
     *     in="query",
     *     description="Old token",
     *     example="thisisatokenlol",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Login")
     * @Route("/login/refresh", methods={"POST"})
     */
    public function refresh(AnnuaireService $annuaire, Request $request)
    {
        $token = $request->query->get('token', '');
        $cookie = $request->cookies->all() ?? [];

        if (!trim($token)) {
            throw new BadRequestHttpException('Token is empty');
        }

        ['token' => $token, 'error' => $error] = $annuaire->refreshToken($token, $cookie);

        return $this->json(
            $error ?? $token
        );
    }

    /**
     * @OA\Response (
     *     response="200",
     *     description="Register info",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(
     *             @OA\Property(property="redirect", type="string", example="https://www.tela-botanica.org/inscription/"),
     *             @OA\Property(property="text", type="string", example="Register here plz"),
     *         )
     *     )
     * )
     * @OA\Tag(name="Login")
     * @Route("/register", methods={"GET"})
     */
    public function register(AnnuaireService $annuaire)
    {
        return $this->json([
            'redirect' => $annuaire->getRegisterUrl(),
            'text' => 'Smart’Flore propose la connexion avec un compte Tela Botanica, si besoin créez donc le votre depuis le site tela-botanica.org. Un mail de validation vous parviendra pour valider votre email et activer votre compte. Une fois votre compte actif vous pourrez vous connecter ici.'
        ]);
    }
}
