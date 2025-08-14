<?php

namespace App\Service;

use App\Entity\Occurrence;
use App\Entity\Sentier;
use App\Model\User;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AnnuaireService
{
    private $loginBaseUrl;
    private $registerUrl;
    private $cookieName;
    private $admins;
    private $annuaireFindUser;
    private $trails;
    private $client;

    public function __construct(
        string $annuaireLoginBaseUrl,
        string $annuaireRegisterUrl,
        string $annuaireCookieName,
        string $admins,
        string $annuaireFindUser,
        TrailsService $trailsService
    ) {
        $this->loginBaseUrl = $annuaireLoginBaseUrl;
        $this->registerUrl = $annuaireRegisterUrl;
        $this->cookieName = $annuaireCookieName;
        $this->admins = $admins;
        $this->annuaireFindUser = $annuaireFindUser;
        $this->trails = $trailsService;
        $this->client = HttpClient::create();
    }

    public function getToken(string $login, string $password): array
    {
        $client = new HttpBrowser();
        $error = null;

        $client->request('GET', sprintf(
            $this->loginBaseUrl.'login?login=%s&password=%s',
            urlencode($login), urlencode($password)));
        $response = $client->getResponse();

        if (200 !== $response->getStatusCode()) {
            $error = 'error';
            if (401 === $response->getStatusCode()) {
                $error = "Erreur. Mauvais login ou mot de passe";
            }
        }

        $cookieJar = $client->getCookieJar();
        $cookie = $cookieJar->get($this->cookieName);

        return [
            'token' => json_decode($response->getContent(), true)['token'] ?? null,
            'cookie' => $cookie,
            'error' => $error
        ];
    }

    public function refreshToken(string $token, ?array $cookie = null): array
    {
        $error = null;
        $client = CookieAwareClient::create($cookie);
        $client->request('GET', $this->loginBaseUrl.'identite?token='.$token);
        $response = $client->getResponse();

        if (200 !== $response->getStatusCode()) {
            $error = 'error';
            if (400 === $response->getStatusCode()) {
                $error = 'Erreur lors du rafraichissement du token. Veuillez vous reconnecter';
            }
        }
		
        return [
            'token' => json_decode($response->getContent(), true)['token'] ?? null,
            'error' => $error
        ];
    }

    /**
     * @return User|string
     */
    public function getUser(string $token, ?array $cookie = null): User
    {
        ['token' => $token, 'error' => $error] = $this->refreshToken($token, $cookie);
        if ($error) {
            return $error;
        }

        $tokenInfos = $this->decodeToken($token);
        $user = new User();
        $user->setEmail($tokenInfos['sub'])
            ->setId($tokenInfos['id'])
            ->setName($tokenInfos['intitule'])
            ->setAvatar(($tokenInfos['avatar'] ?? ''));

        $userTrails = $this->trails->getAllUserTrails($user);
        $user->setTrails($userTrails);

        return $user;
    }

    public function getUserInfos(string $token, ?array $cookie = null): User
    {
        ['token' => $token, 'error' => $error] = $this->refreshToken($token, $cookie);
        if ($error) {
            return $error;
        }

        $tokenInfos = $this->decodeToken($token);
        $user = new User();
        $user->setEmail($tokenInfos['sub'])
            ->setId($tokenInfos['id'])
            ->setName($tokenInfos['intitule'])
            ->setAvatar(($tokenInfos['avatar'] ?? ''));

        return $user;
    }

    public function getUserFromRequest(Request $request): array
    {
        try {
            $token = $this->getRequestToken($request);
            if (!$token) {
                return ['user' => null, 'token'=> null, 'error' => 'No token found, veuillez vous reconnecter'];
            }
            $user = $this->getUserInfos($token);
            return ['user' => $user, 'token'=>$token, 'error' => null];
        } catch (\Exception $e) {
            return ['user' => null, 'token'=> null, 'error' => 'Erreur d\'authentification: '. $e->getMessage()];
        }
    }

    /**
     * Decodes a formerly validated JWT token and returns the data it contains
     * (payload / claims)
     */
    public function decodeToken($token) {
        $parts = explode('.', $token);
        $payload = $parts[1];
        $payload = $this->urlsafeB64Decode($payload);
        $payload = json_decode($payload, true);

        return $payload;
    }

    /**
     * Method compatible with "urlsafe" base64 encoding used by JWT lib
     */
    public function urlsafeB64Decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function getRegisterUrl(): string
    {
        return $this->registerUrl;
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    public function getRequestToken(Request $request): ?string
    {
        $token = null;
        $cookie = $request->cookies->get($this->getCookieName()) ?? null;
        $headerAuthorization = $request->headers->get('Authorization') ?? null;
        $tokenInQuery = $request->query->get('token', '');

        if ($cookie){
            $token = $request->cookies->get($this->getCookieName());
        } else if ($headerAuthorization) {
            $token = $headerAuthorization;
        } else {
            $token = $tokenInQuery;
        }

        $cookie = [
            $this->getCookieName() => $token
        ];
        if ($token) {
            ['token' => $token, 'error' => $error] = $this->refreshToken($token, $cookie);
        } else {
            $error = 'No token found, veuillez vous reconnecter';
            throw new \Exception($error);
        }

        return $token;
    }

    public function isAdmin(User $user): bool
    {
        $listeAdmins = explode(',',  $this->admins);
        return in_array($user->getEmail(), $listeAdmins);
    }

    public function canUpdateTrail(User $user, Sentier $trail): bool
    {
        $isAdmin = $this->isAdmin($user);
        if ($isAdmin) {
            return true;
        }

        return $trail->getAuthorId() === $user->getId();
    }

    public function canUpdateOccurrence(User $user, Occurrence $occurrence): bool
    {
        $isAdmin = $this->isAdmin($user);
        if ($isAdmin) {
            return true;
        }

        return $occurrence->getUserId() === $user->getId();
    }

    public function listAdmin(): array
    {
        return explode(',',  $this->admins);
    }

    public function findUserIdByEmail(string $email)
    {
        $response = $this->client->request(
            'GET', $this->annuaireFindUser.'/'.$email
        );

        try {
            if (200 !== $response->getStatusCode()) {
                if (500 === $response->getStatusCode()) {
                    // annuaire returns a 500 when email is not found
                    return false;
                }

                throw new \Exception(sprintf(
                    'Annuaire is not happy, getting some %d error for: "%s"',
                    $response->getStatusCode(),
                    $response->getInfo('url')
                ));
            }
        } catch (\Exception $e) {
            return false;
        }

        $userData = $response->getContent();
        if ('[]' === $userData) {
            return false;
        }
        $userData = json_decode($this->fixDumbAnnuaireDataStructure($userData));
        return $userData->id;
    }

    private function fixDumbAnnuaireDataStructure(string $data): string
    {
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        $email = array_key_first($data);
        $id = $data[$email]['id'];
        $intitule = $data[$email]['intitule'] ?? $data[$email]['pseudo'] ?? $data[$email]['prenom'] ?? 'anonymous';

        return json_encode([
            'id' => $id,
            'email' => $email,
            'intitule' => $intitule,
        ], JSON_THROW_ON_ERROR);
    }
}
