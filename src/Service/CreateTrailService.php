<?php

namespace App\Service;

use App\Model\CreateOccurrenceDto;
use App\Model\CreateTrailDto;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CreateTrailService
{
    private $client;
    private $smartfloreLegacyApiBaseUrl;
    private $authorizeToken;
    private $annuaire;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        AnnuaireService $annuaire
    ) {
        /**
         * @var $client HttpClientInterface
         */
        $this->client = HttpClient::create();
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
        $this->annuaire = $annuaire;
    }

    public function process(CreateTrailDto $trail): void
    {
        $this->createTrail($trail);
        foreach ($trail->getOccurrences() as $occurrence) {
            $this->getCardTag($occurrence);
            $this->addSpeciesToTrail($trail, $occurrence);
        }
        $this->addLocation($trail);
        $this->addPmrAndSeasons($trail);
        if ($this->isTrailEligible($trail)) {
            $email = $this->annuaire->getUser($this->getAuth())->getEmail();
            $this->submitTrailToReview($trail, $email);
        }
    }

    public function createTrail(CreateTrailDto $trail): void
    {
        $trailName = $trail->getName();
        if (!$this->isTrailNameAvailable($trailName)) {
            $trailName = $this->addRandomIntegerSuffixToAlreadyUsedTrailNameUntilNameIsFreeThisMethodNameIsTooLong($trailName);
        }

        $response = $this->client->request('PUT', $this->smartfloreLegacyApiBaseUrl.'sentier/',
            [
            'body' => json_encode(['sentierTitre' => $trailName]),
            'headers' => [
                'Authorization: '.$this->getAuth(),
                'Auth: '.$this->getAuth()
            ]
            ]);
        if (200 !== $response->getStatusCode() || 'OK' !== $response->getContent()) {
            throw new \Exception('Erreur lors de la création du sentier.');
        }
    }

    public function addSpeciesToTrail(CreateTrailDto $trail, CreateOccurrenceDto $occurrence): void
    {
        $response = $this->client->request('PUT', $this->smartfloreLegacyApiBaseUrl.'sentier-fiche/', [
            'body' => json_encode([
                'sentierTitre' => $trail->getName(),
                'pageTag' => $occurrence->getCardTag(),
            ]),
            'headers' => [
                'Authorization: '.$this->getAuth(),
                'Auth: '.$this->getAuth()
            ],
        ]);

        if (200 !== $response->getStatusCode() || 'OK' !== $response->getContent()) {
            throw new \Exception('Erreur lors de l\'ajout d\'espèces au sentier.');
        }
    }


    public function addLocation(CreateTrailDto $trail): void
    {
        // it's messy, sorry
        $array = [];
        $array['sentierTitre'] = $trail->getName();
        $array['sentierLocalisation']['sentier'] = $trail->getPosition()->getStart();
        // prepare complex array structure (as in legacy app)
        foreach ($trail->getOccurrences() as $occurrence) {
            $complex[$occurrence->getCardTag()][] = $occurrence;
        }
        foreach ($complex as $cardTag) {
            foreach ($cardTag as $i => $occurrence) {
                /**
                 * @var CreateOccurrenceDto $occurrence
                 */
                $array['sentierLocalisation']['individus'][$occurrence->getCardTag().'#'.$i] = [
                    'ficheTag' => $occurrence->getCardTag(),
                    'lat' => $occurrence->getPosition()['lat'],
                    'lng' => $occurrence->getPosition()['lng']
                ];
            }
        }
        $array['sentierDessin'] = $trail->getPath()->getGeoJson();

        $response = $this->client->request('PUT', $this->smartfloreLegacyApiBaseUrl.'sentier-localisation/', [
            'body' => json_encode($array),
            'headers' => [
                'Authorization: '.$this->getAuth(),
                'Auth: '.$this->getAuth()
            ],
        ]);

        if (200 !== $response->getStatusCode() || 'OK' !== $response->getContent()) {
            throw new \Exception('Erreur lors de l\'ajout de la localisation.');
        }
    }

    public function addPmrAndSeasons(CreateTrailDto $trail): void
    {
        $response = $this->client->request('PUT', $this->smartfloreLegacyApiBaseUrl.'sentier-pmr-seasons/', [
            'body' => json_encode([
                'sentierTitre' => $trail->getName(),
                'pmr' => $trail->getPrm(),
                'best_season' => $trail->getBestSeason()
            ]),
            'headers' => [
                'Authorization: '.$this->getAuth(),
                'Auth: '.$this->getAuth()
            ],
        ]);

        if (200 !== $response->getStatusCode() || 'OK' !== $response->getContent()) {
            throw new \Exception('Erreur lors de l\'ajout pmr et best-seasons.');
        }
    }

    public function submitTrailToReview(CreateTrailDto $trail, string $authorEmail): void
    {
        $response = $this->client->request('PUT', $this->smartfloreLegacyApiBaseUrl.'sentier-validation/', [
            'body' => json_encode([
                'sentierTitre' => $trail->getName(),
                'sentierAuteur' => $authorEmail,
            ]),
            'headers' => [
                'Authorization: '.$this->getAuth(),
                'Auth: '.$this->getAuth()
            ],
        ]);

        if (200 !== $response->getStatusCode() || 'OK' !== $response->getContent()) {
            throw new \Exception('Erreur lors de l\envoi du sentier en validation.');
        }
    }

    public function isTrailEligible(CreateTrailDto $trail): bool
    {
        return (10 <= count($trail->getOccurrences()));
    }

    public function getCardTag(CreateOccurrenceDto $occurrence): void
    {
        // https://beta.tela-botanica.org/smart-form/services/Pages.php?referentiel=BDTFX&referentiel_verna=nvjfl&recherche=Acer+campestre&pages_existantes=false&nom_verna=false&debut=0&limite=1
        // {"pagination":{"total":"11"},"resultats":[{"existe":true,"favoris":false,"tag":"SmartFloreBDTFXnt8522","time":"2015-09-10 11:14:08","owner":"AdelineMoreau","user":"adansonia","nb_revisions":"1","infos_taxon":{"num_taxonomique":"8522","nom_sci":"Acer campestre","nom_sci_complet":"Acer campestre L. [1753, Sp. Pl., 2 : 1055]","retenu":"true","num_nom":"141","referentiel":"BDTFX","noms_vernaculaires":[]},"id":"43415","latest":"Y"}]}
        $url = str_replace('Sentiers', 'Pages', $this->smartfloreLegacyApiBaseUrl);
        $response = $this->client->request('GET', $url.'sentier/', [
            'query' => [
                'recherche' => $occurrence->getScientificName(),
                'referentiel' => $occurrence->getTaxonRepository(),
                'limite' => 1,
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Erreur lors de la récupération de la card.');
        }

        $fiches = json_decode($response->getContent(), true)['resultats'];
        if (!count($fiches)) {
            throw new \Exception('No card tag found for '.$occurrence->getTaxonRepository().':'.$occurrence->getScientificName());
        }

        $occurrence->setCardTag($fiches[0]['tag']);
    }

    public function isTrailNameAvailable(string $trailName): bool
    {
        $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentier-informations/?sentierTitre='.$trailName);
        $statusCode = $response->getStatusCode();


        switch ($statusCode) {
            case 200:
                return false; // trail name already used
            case 404:
                return true; // trail not found (or wrong service url... thx shitty status code)
            default:
                throw new \Exception("Unattended status code: $statusCode (instead of 200 or 404)");
        }
    }

    public function addRandomIntegerSuffixToAlreadyUsedTrailNameUntilNameIsFreeThisMethodNameIsTooLong(string $trailName): string
    {
        do {
            $trailName.=random_int(1,10);
        } while (!$this->isTrailNameAvailable($trailName));

        return $trailName;
    }

    public function setAuth(string $token): void
    {
        $this->authorizeToken = $token;
    }

    private function getAuth(): string
    {
        if (!$this->authorizeToken) {
            throw new \Exception('Missing authorize token, please set before using this service');
        }
        return $this->authorizeToken;
    }
}
