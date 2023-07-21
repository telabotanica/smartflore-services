<?php

namespace App\Service;

use App\Model\Image;
use App\Model\Trail;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;
use League\Geotools\Polygon\Polygon;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;

class TrailsService
{
    private $client;
    private $cache;
    private $smartfloreLegacyApiBaseUrl;
    private $userHashSecret;
    private $router;
    private $efloreService;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        string $userHashSecret,
        CacheInterface $cache,
        UrlGeneratorInterface $router,
        EfloreService $efloreService
    ) {
        $this->client = HttpClient::create();
        $this->cache = $cache;
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
        $this->userHashSecret = $userHashSecret;
        $this->router = $router;
        $this->efloreService = $efloreService;
    }

    /**
     * @param bool $refresh
     * @return Trail[]
     */
    public function getTrails(bool $refresh = false)
    {
        if ($refresh) {
            $this->buildTrailsListCache();
            $this->buildAllTrailsCache();
        }

        $trailsCache = $this->cache->getItem('trails.list');
        $trailsList = $trailsCache->get();

        $trails = [];
        if ($trailsList) {
            foreach ($trailsList as $trail) {
                $trailName = self::extractTrailName($trail);
                $trailCache = $this->cache->getItem('trails.trail.' . $trailName);
                $trail = $trailCache->get();
                if ($trail){
                    $this->collectOccurrencesTaxonInfos($trail);
                    $this->collectTrailImages($trail);
                }
                $trails[] = $trail;
            }
        }

        return $trails;
    }

    /**
     * @return Trail[]
     */
    public function getTrailsList()
    {
        $trails = [];
        $trailsCache = $this->cache->getItem('trails.list');
        if ($trailsCache->isHit()) {
            $trailsList = $trailsCache->get();

            foreach ($trailsList as $trail) {
                $trailName = self::extractTrailName($trail);
                $trailCache = $this->cache->getItem('trails.trail.'.$trailName);
                if ($trailCache->isHit()) {
                    $trail = $trailCache->get();
					if (strpos($trail->getNom(), '_deleted_at_') === false){
						$this->findOneImagePlease($trail);
						$trails[] = $trail;
					}
                }
            }
        }

        return $trails;
    }

    public function getTrail(string $trailName, bool $refresh = false)
    {
        $trailCache = $this->cache->getItem('trails.trail.'.$trailName);
        if ($refresh || !$trailCache->isHit()) {
            $this->buildTrailCache($trailName);
        }
        $trail = $trailCache->get();
        // Si on a pas de trail, on ne recherche pas les infos de taxon sinon -> erreur lors du refresh
//        print_r($trail);
        if ($trail){
            $this->collectOccurrencesTaxonInfos($trail);
            $this->collectTrailImages($trail);
        }

        return $trail;
    }

    public function findOneImagePlease(Trail $trail): void
    {
        $occurrencesImages = $this->getTrailSpecieImages($trail->getNom());
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxo();
            $images = $occurrencesImages[$taxon->getReferentiel()][$taxon->getTaxonomicId()] ?? [];
            $images += $this->efloreService->getCardSpeciesImages(
                $taxon->getReferentiel(), $taxon->getNumNom()
            );
            if (isset($images[0]) && Image::class === get_class($images[0])) {
                $trail->setImage($images[0]);
            }
        }
    }

    public function getTrailSpecieImages(string $trailName, bool $refresh = false)
    {
        $trailSpecieImagesCache = $this->cache->getItem('trails.trail.'.$trailName.'.images');

        if ($refresh || !$trailSpecieImagesCache->isHit()) {
            // https://www.tela-botanica.org/smart-form/services/Sentiers.php/sentier-illustration-fiche/?sentierTitre=Sentier%20botanique%20de%20la%20r%C3%A9serve%20naturelle%20Tr%C3%A9sor
            $url = $this->smartfloreLegacyApiBaseUrl
                .'sentier-illustration-fiche/?sentierTitre='.urlencode($trailName);
            $response = $this->client->request('GET', $url, [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Erreur lors de la récupération des images espèces.');
            }
            $images = json_decode($response->getContent(), true);

            $res = [];
            foreach ($images as $key => $val) {
                $matches = [];
                if (preg_match('@SmartFlore(\w+)nt(\d+)@', $key, $matches)) {
                    $taxonRepo = strtolower($matches[1]);
                    $taxonId = $matches[2];
                    $res[$taxonRepo][$taxonId] = array_map(static function ($img) {
                        // @todo: find a service to get author info by image id
                        return new Image((int)$img['id'], $img['url'], 'Inconnu');
                    }, $val['illustrations']);
                }
            }

            $trailSpecieImagesCache->set($res);
            $this->cache->save($trailSpecieImagesCache);
        }

        return $trailSpecieImagesCache->get();
    }

    public function getTrailName(int $id): string
    {
        $trails = $this->getTrailsList();
        foreach ($trails as $trail) {
            if ($trail->getId() === $id) {
                return $trail->getNom();
            }
        }
        return '';
    }

    public static function extractTrailName(Trail $trail): string
    {
        if ($trail->getDetails()) {
            $parts = explode('/', $trail->getDetails());
            return urldecode(end($parts));
        }

        throw new \Exception('missing trail name');
    }

    /**
     * Get image collection
     */
    private function collectTrailImages(Trail $trail): void
    {
        $occurrencesImages = $this->getTrailSpecieImages($trail->getNom());
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxo();

            $images = $occurrencesImages[$taxon->getReferentiel()][$taxon->getTaxonomicId()] ?? [];
            $images += $this->efloreService->getCardSpeciesImages(
                $taxon->getReferentiel(), $taxon->getNumNom());

            $coste = $this->efloreService->getCardCosteImage(
                $taxon->getReferentiel(), $taxon->getTaxonomicId());
            if ($coste) {
                $images[] = $coste;
            }

            $occurrence->setImages(array_filter($images));

            if (!$trail->getImage() && $occurrence->getFirstImage()) {
                $trail->setImage($occurrence->getFirstImage());
            }
        }
    }

    private function buildTrailImagesCache(Trail $trail): void
    {
        $occurrencesImages = $this->getTrailSpecieImages($trail->getNom(), true);
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxo();

            $images = $occurrencesImages[$taxon->getReferentiel()][$taxon->getTaxonomicId()] ?? [];
            $images += $this->efloreService->getCardSpeciesImages(
                $taxon->getReferentiel(), $taxon->getNumNom(), true);

            $coste = $this->efloreService->getCardCosteImage(
                $taxon->getReferentiel(), $taxon->getTaxonomicId(), true);
            if ($coste) {
                $images[] = $coste;
            }

            $occurrence->setImages(array_filter($images));

            if (!$trail->getImage() && $occurrence->getFirstImage()) {
                $trail->setImage($occurrence->getFirstImage());
            }
        }
    }

    public static function getTrailLength(Trail $trail): float
    {
        $geotools = new Geotools();
        $distance = 0;

        $points = [];
		if ($trail->getChemin()){
			foreach ($trail->getChemin()->getCoordinates() as $point) {
				if ($point){
					$points[] = new Coordinate(array_values($point));
				}
			}
			
			foreach ($points as $point) {
				$next = next($points);
				if ($next) {
					$distance += $geotools->distance()->setFrom($point)->setTo($next)->flat();
				}
			}
			
			return $distance;
		} else {
			return 0;
		}
    }

    /**
     * @return Trail[]
     */
    public function getTrailsInsideBoundaries(Polygon $polygon): array
    {
        $trails = [];
        foreach ($this->getTrails() as $trail) {
            $coordinate = new Coordinate(array_values($trail->getStartPosition()));
            if ($polygon->pointInPolygon($coordinate)) {
                $trails[] = $trail;
            }
        }

        return $trails;
    }

    /**
     * Get full taxonomic infos, vernacular names, external links
     */
    public function collectOccurrencesTaxonInfos(Trail $trail): void
    {
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxo();
            $taxon = $this->efloreService->getTaxon(
                $taxon->getReferentiel(), $taxon->getNumNom());
            $occurrence->setTaxo($taxon);
        }
    }

    public function buildOccurrencesTaxonInfos(Trail $trail): void
    {
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxo();
            $taxon = $this->efloreService->getTaxon(
                $taxon->getReferentiel(), $taxon->getNumNom(), true);
            $occurrence->setTaxo($taxon);
        }
    }

    /**
     * Call private route for user's trails list (for /me route)
     */
    public function getAllUserTrails(string $token, $user): array
    {
		$userTrailsList = [];
		$response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentier/', [
			'timeout' => 1800,
			'headers' => [
				'Authorization' => $token,
				'Auth' => $token
			],
		]);
	
		if (200 !== $response->getStatusCode()) {
			throw new \Exception('Something went wrong with user sentier list.');
		}
	
		foreach (json_decode($response->getContent(), true)['resultats'] as $trail) {
			if (isset($trail['auteur']) && $trail['auteur'] == $user->getEmail() && !$trail['dateSuppression']) {
				$userTrail = new Trail();
				
				$displayName = '';
				$detail = '';
				$image = null;
				$position = null;
				$occurrencesCount = 0;
				$pathLength = 0;
				
				$trailDetail = $this->getTrailInCache($trail['titre']);
				if ( !$trailDetail) {
					$trailInfos = null;
					$trailInfos = $this->getDraftTrailInfo($trail['titre']);
					if ($trailInfos) {
						if ($trailInfos->getOccurrencesCount() > 0) {
							$occurrencesCount = $trailInfos->getOccurrencesCount();
							$pathLength = $trailInfos->getPathLength();
							$trailInfos = $this->getImageForMe($trailInfos);
							$hasAnImage = false;
							foreach ($trailInfos->getOccurrences() as $trailOccurrence){
								if ($trailOccurrence->getFirstImage()){
									$hasAnImage = true;
								}
								if ($hasAnImage){
									$image = $trailOccurrence->getFirstImage();
									break;
								}
							}
							if (!$hasAnImage){
								$image = null;
							}
						}
						
						$displayName = $trailInfos->getDisplayName();
						$detail = $trailInfos->getDetails();
						$position = $trailInfos->getPosition();
					}
					
					$userTrail->setId($trail['id'])
						->setNom($trail['titre'])
						->setDisplayName($displayName)
						->setAuteur($trail['auteur'])
						->setDetails($detail)
						->setPathLength($pathLength)
						->setOccurrencesCount($occurrencesCount)
						->setImage($image)
						->setStatus($trail['etat'] ?? 'draft');
					if ($position){
						$userTrail->setPosition($position);
					}
				} else {
					$userTrail->setId($trailDetail->getId())
						->setNom($trail['titre'])
						->setDisplayName($trailDetail->getNom())
						->setAuteur($trail['auteur'])
						->setOccurrencesCount($trailDetail->getOccurrencesCount())
						->setDetails($trailDetail->getDetails())
						->setImage($trailDetail->getImage())
						->setPathLength($trailDetail->getPathlength())
						->setStatus($trail['etat'] ?? 'draft');
					
					if ($trailDetail->getPosition() != null) {
						$userTrail->setPosition($trailDetail->getPosition());
					}
				}
				$userTrailsList[] = $userTrail;
			}
		}
		return $userTrailsList;
    }
	
	public function getDraftTrailInfo($trailName){
		$response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentiers/'.urlencode($trailName), [
			'timeout' => 120,
			'headers' => [
				'Accept: application/json',
			],
		]);
		
		if (200 !== $response->getStatusCode()) {
			if ('Ce sentier n\'existe pas' === $response->getContent(false)) {
				return null;
			}
			return null;
		}
		
		$extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
		$normalizer = [
			new ArrayDenormalizer(),
			new ObjectNormalizer(null, null, null, $extractor),
		];
		$serializer = new Serializer($normalizer, [new JsonEncoder()]);
		$espece = false;
		
		if (isset(json_decode($response->getContent(), true)['occurrences'])){
			$occurrences = json_decode($response->getContent(), true)['occurrences'];
			
			foreach ($occurrences as $occurrence){
				if (isset($occurrence['taxo']['espece'])){
					$espece = true;
				}
			}
		}
		
		if ($espece) {
			$trail = $serializer->deserialize($response->getContent(), Trail::class, 'json', [
				\Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
			]);
			/**
			 * @var Trail $trail
			 */
			$trail->setDisplayName($trail->getNom());
			$trail->setNom($trailName);
			$trail->setDetails($this->router->generate('show_trail', ['id' => $trail->getNom() ], UrlGeneratorInterface::ABSOLUTE_URL));
			return $trail;
		}
	}
	
	public function getImageForMe($trail){
		$occurrencesImages = $this->getTrailSpecieImages($trail->getNom(), true);
		foreach ($trail->getOccurrences() as $occurrence) {
			$taxon = $occurrence->getTaxo();
			
			$images = $occurrencesImages[$taxon->getReferentiel()][$taxon->getTaxonomicId()] ?? [];
			$images += $this->efloreService->getCardSpeciesImages(
				$taxon->getReferentiel(), $taxon->getNumNom(), true);
			
			$coste = $this->efloreService->getCardCosteImage(
				$taxon->getReferentiel(), $taxon->getTaxonomicId(), true);
			if ($coste) {
				$images[] = $coste;
			}
			
			$occurrence->setImages(array_filter($images));
			
			if (!$trail->getImage() && $occurrence->getFirstImage()) {
				$trail->setImage($occurrence->getFirstImage());
			}
		}
		return $trail;
	}
	
	public function getTrailInCache(string $trailName)
	{
		$trailCache = $this->cache->getItem('trails.trail.'.$trailName);
		if (!$trailCache->isHit()) {
			return null;
		} else {
			$trail = $trailCache->get();
			// Si on a pas de trail, on ne recherche pas les infos de taxon sinon -> erreur lors du refresh
			if ($trail){
				$this->collectOccurrencesTaxonInfos($trail);
				$this->collectTrailImages($trail);
			}
			return $trail;
		}
	}

    /**
     * Filter trails list to get user trails
     */
    public function getPublishedUserTrails(string $email): array
    {
        $hash = hash('sha3-224', $email.$this->userHashSecret);
        $userTrailsList = [];

        $trails = $this->getTrails();
        foreach ($trails as $trail) {
            if ($trail && $trail->getAuthorId() === $hash) {
                $userTrailsList[] = $trail;
            }
        }

        return $userTrailsList;
    }

    public function buildTrailsListCache()
    {
        $trailsCache = $this->cache->getItem('trails.list');

        $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentiers/', [
            'timeout' => 180,
            'headers' => [
                'Accept: application/json',
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Erreur lors de la creation des sentiers en cache.');
        }

        $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
        $normalizer = [
            new ArrayDenormalizer(),
            new ObjectNormalizer(null, null, null, $extractor),
        ];
        $serializer = new Serializer($normalizer, [new JsonEncoder()]);

        $trails = $serializer->deserialize($response->getContent(), 'App\Model\Trail[]', 'json', [
            'remove_empty_tags' => true
        ]);

        $trailsCache->set($trails);
        $this->cache->save($trailsCache);
    }

    public function buildAllTrailsCache()
    {
        $trailsCache = $this->cache->getItem('trails.list');
        if (!$trailsCache->isHit()) {
            $this->buildTrailsListCache();
        }
        $trails = $trailsCache->get();

        /**
         * @var $trail Trail
         */
        foreach ($trails as $trail) {
            $trailName = self::extractTrailName($trail);
			try {
				$this->buildTrailCache($trailName);
				
				$trailCache = $this->cache->getItem('trails.trail.'.$trailName);
				$trail = $trailCache->get();
				if ($trail){
					$this->buildOccurrencesTaxonInfos($trail);
					$this->buildTrailImagesCache($trail);
				}
			} catch (\Exception $e){
				print_r('erreur lors de la création du build trail cache du sentier: ');
				print_r($trailName);
				print_r($e->getMessage());
				continue;
			}
        
        }
    }

    public function buildTrailCache(string $trailName)
    {
        $trailCache = $this->cache->getItem('trails.trail.'.$trailName);
        $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentiers/'.urlencode($trailName), [
            'timeout' => 120,
            'headers' => [
                'Accept: application/json',
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            if ('Ce sentier n\'existe pas' === $response->getContent(false)) {
                throw new TrailNotFoundException('This trail does not exist');
            }
            throw new \Exception('Erreur lors de la mise en cache du sentier '.$trailName);
        }

        $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
        $normalizer = [
            new ArrayDenormalizer(),
            new ObjectNormalizer(null, null, null, $extractor),
        ];
        $serializer = new Serializer($normalizer, [new JsonEncoder()]);

        $occurrences = json_decode($response->getContent(), true)['occurrences'];
        $espece = false;
        foreach ($occurrences as $occurrence){
            // Vérification si l'espèce est bien renseignée (en cas d'erreur lors de la récupération de fichhes) -> Pour résoudre bug de refresh du cache
            if (isset($occurrence['taxo']['espece'])){
                $espece = true;
            }
        }
        if ($espece){
				$trail = $serializer->deserialize($response->getContent(), Trail::class, 'json', [
					\Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
				]);
				/**
				 * @var Trail $trail
				 */
				$trail->computeOccurrencesCount();
				$trail->setDisplayName($trail->getNom());
				$trail->setNom($trailName);
				$trail->setDetails($this->router->generate('show_trail', [
					'id' => $trail->getNom()
				], UrlGeneratorInterface::ABSOLUTE_URL));
				$trailCache->set($trail);
				$this->cache->save($trailCache);
        }
    }
}
