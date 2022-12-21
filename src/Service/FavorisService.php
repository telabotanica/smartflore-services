<?php

namespace App\Service;

use App\Model\Favorite;
use App\Model\User;
use PHPUnit\Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class FavorisService
{
    private \Symfony\Contracts\HttpClient\HttpClientInterface $client;

    public function __construct(
        AnnuaireService $annuaire
    ) {
        $this->client = HttpClient::create();
    }

    /**
     * @param string $token
     * @param array $tokenInfos
     * @return Favorite[]
     * @throws \Exception|TransportExceptionInterface
     */
    public function getFavorisList(string $token, array $tokenInfos): array
    {
        $favorisList = [];
        $url = 'https://www.tela-botanica.org/smart-form/services/Favoris.php/';

            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Auth' => $token,
                    'Authorization' => $token
                ]
            ]);

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Something went wrong with user favorite list.');
        }

        $favoritesData = json_decode($response->getContent(), true)['resultats'];

        foreach ($favoritesData as $favorite) {
            $userFavorite = new Favorite();
            $userFavorite->setScientificName($favorite['infos_taxon']['nom_sci']);
            $userFavorite->setTaxonId($favorite['infos_taxon']['num_taxonomique']);
            $userFavorite->setUser($tokenInfos['sub']);
            $userFavorite->setUserId($tokenInfos['id']);
            $userFavorite->setTaxonRepository($favorite['infos_taxon']['referentiel']);

            $favorisList[] = $userFavorite;
        }

        return $favorisList;
    }

}