<?php

namespace App\Service;

use App\Model\User;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AnnuaireService
{
    private $loginBaseUrl;
    private $registerUrl;
    private $cookieName;
    private $trails;

    public function __construct(
        string $annuaireLoginBaseUrl,
        string $annuaireRegisterUrl,
        string $annuaireCookieName,
        TrailsService $trailsService
    ) {
        $this->loginBaseUrl = $annuaireLoginBaseUrl;
        $this->registerUrl = $annuaireRegisterUrl;
        $this->cookieName = $annuaireCookieName;
        $this->trails = $trailsService;
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
                $error = 'failed authentication with given login and password';
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
                $error = 'failed refresh token, must relogin';
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
    public function getUser(string $token, ?array $cookie = null)
    {
        ['token' => $token, 'error' => $error] = $this->refreshToken($token, $cookie);
        if ($error) {
            return $error;
        }

        $tokenInfos = $this->decodeToken($token);
        $user = new User();
        $user->setEmail($tokenInfos['sub'])
            ->setName($tokenInfos['intitule'])
            ->setAvatar(($tokenInfos['avatar'] ?? ''));

        $userTrails = $this->trails->getAllUserTrails($token, $user);
        $user->setTrails($userTrails);

        return $user;
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
}
