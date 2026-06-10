<?php

namespace App\Controller;

use Exception;
use App\Model\Login;
use App\Service\AnnuaireService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class LoginController extends AbstractController
{
    public function __construct(private readonly AnnuaireService $annuaire, private readonly SerializerInterface $serializer)
    {
    }
    /**
     * @OA\Response (
     *     response="200",
     *     description="A token (in body) and a cookie (in headers)",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string", example="thisisatokenlol")
     *     )
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing login informations",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Login::class)
     *     )
     * )
     * @OA\Tag(name="Login")
     */
    #[Route(path: '/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
		$loginInfos = $this->serializer->deserialize($request->getContent(), Login::class, 'json');
		$login = $loginInfos->getLogin();
		$password = $loginInfos->getPassword();

        if (!trim($login) || !trim($password)) {
            throw new BadRequestHttpException('Login or Password are empty');
        }

        ['token' => $token, 'cookie' => $cookie, 'error' => $error] = $this->annuaire->getToken($login, $password);

        $response = new JsonResponse($error ?? $token);
		
        if ($cookie) {
            $response->headers->setCookie(
                Cookie::fromString((string) $cookie)
            );
			
			return $response;
        } else {
			return new JsonResponse($response->getContent(), Response::HTTP_UNAUTHORIZED, [], true);
		}

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
     */
    #[Route(path: '/login/refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $token = $request->query->get('token', null);
        if (!$token) {
            $token = $request->headers->get('Authorization') ?? '';
        }
        $cookie = $request->cookies->all() ?? [];

        if (!trim($token)) {
            throw new BadRequestHttpException('Token is empty');
        }

        ['token' => $token, 'error' => $error] = $this->annuaire->refreshToken($token, $cookie);

        return $this->json(
            $error ?? $token
        );
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
     */
    #[Route(path: '/login/refreshv2', methods: ['GET'])]
    public function refreshV2(Request $request): Response|JsonResponse
    {
        $forceCookie = $request->query->get('cookie', null);

        if (!$forceCookie){
            $token = $request->query->get('token', null);
            if (!$token) {
                $token = $request->headers->get('Authorization') ?? '';
            }
        } else {
            $token = $this->annuaire->getRequestToken($request);
        }

        $cookie = $request->cookies->all() ?? [];

        ['token' => $token, 'duration' => $duration, 'token_id' => $token_id, 'error' => $error] = $this->annuaire->refreshToken($token, $cookie);

        if ($error) {
            return new Response($error, Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['token' => $token, 'duration' => $duration, 'token_id' => $token_id, 'error' => $error]);
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
     */
    #[Route(path: '/register', methods: ['GET'])]
    public function register(): JsonResponse
    {
        return $this->json([
            'redirect' => $this->annuaire->getRegisterUrl(),
            'text' => 'Smart’Flore propose la connexion avec un compte Tela Botanica, si besoin créez donc le votre depuis le site tela-botanica.org. Un mail de validation vous parviendra pour valider votre email et activer votre compte. Une fois votre compte actif vous pourrez vous connecter ici.'
        ]);
    }

    /**
     * @OA\Response (
     *     response="200",
     *     description="Check if user is admin or not",
     *     @OA\JsonContent(
     *         @OA\Schema(type="boolean", example="true")
     *     )
     * )
     * @OA\Tag(name="Login")
     * @OA\get(
     *     summary="check if user is admin or not",
     * )
     */
    #[Route(path: '/admincheck', name: 'user_admincheck', methods: ['GET'])]
    public function checkIfAdmin(Request $request): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la recherche des droits utilisateurs: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $isAdmin = $this->annuaire->isAdmin($user);

        return new JsonResponse(json_encode($isAdmin), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Logout successful"
     * )
     * @OA\Tag(name="Login")
     */
    #[Route(path: '/logout', name: 'user_logout', methods: ['GET'])]
    public function logout(): Response
    {
        ['data' => $data, 'cookie' => $cookie, 'error' => $error] = $this->annuaire->logout();
        $response = new JsonResponse(
            $error ?? $data,
            $error ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK
        );
        if ($cookie) {
            $response->headers->setCookie($cookie);
        }
        return $response;
    }
}
