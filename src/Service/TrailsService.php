<?php

namespace App\Service;

use App\Entity\Sentier;
//use App\Model\Image;
use App\Entity\Image;
use App\Model\Taxon;
use App\Model\Trail;
use App\Service\ImageService;
use App\Repository\SentierRepository;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;
use League\Geotools\Polygon\Polygon;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class TrailsService
{
    private $client;
    private $cache;
    private $smartfloreLegacyApiBaseUrl;
    private $userHashSecret;
    private $router;
    private $efloreService;
    private SentierRepository $sentierRepository;
    private ImageService $imageService;
    private SerializerInterface $serializer;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        string $userHashSecret,
        CacheInterface $cache,
        UrlGeneratorInterface $router,
        EfloreService $efloreService,
        SentierRepository $sentierRepository,
        ImageService $imageService,
        SerializerInterface $serializer
    ) {
        $this->client = HttpClient::create();
        $this->cache = $cache;
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
        $this->userHashSecret = $userHashSecret;
        $this->router = $router;
        $this->efloreService = $efloreService;
        $this->sentierRepository = $sentierRepository;
        $this->imageService = $imageService;
        $this->serializer = $serializer;
    }

    /**
     * @param bool $refresh
     * @return Sentier[]
     * encore dans cacheRefreshCommand et cacheService
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
//                $trailName = self::extractTrailName($trail);
                $trailName = $trail->getNom();
                $trailCache = $this->cache->getItem('trails.trail.' . $trailName);
                $trail = $trailCache->get();
//                if ($trail){
//                    //TODO c'est un doublon si c'est lors de la création ?
////                    $this->collectOccurrencesTaxonInfos($trail);
//                    $this->collectTrailImages($trail);
//                }
                $trails[] = $trail;
            }
        }

        return $trails;
    }

    /**
     * @return Sentier[]
     * Description: get trail list from cache
     * TODO
     */
    public function getTrailsList()
    {
        $trails = [];
        $trailsCache = $this->cache->getItem('trails.list');

        if ($trailsCache->isHit()) {
            $trailsList = $trailsCache->get();
            foreach ($trailsList as $trail) {
                $trailName = $trail->getNom();
                $trailCache = $this->cache->getItem('trails.trail.'.$trailName);

                if ($trailCache->isHit()) {
                    $trail = $trailCache->get();
						$this->imageService->findOneImagePlease($trail);
						$trails[] = $trail;
                }
            }
        }

        return $trails;
    }

    //TODO: encore utile? -> encore dans cacheRefreshCommand
    public function getTrail(string $trailName, bool $refresh = false)
    {
        $trailCache = $this->cache->getItem('trails.trail.'.$trailName);
        if ($refresh || !$trailCache->isHit()) {
            $this->buildTrailCache($trailName);
        }
        $trail = $trailCache->get();
        // Si on a pas de trail, on ne recherche pas les infos de taxon sinon -> erreur lors du refresh
//        print_r($trail);
//        if ($trail){
////            $this->collectOccurrencesTaxonInfos($trail);
//            $this->imageService->collectTrailImages($trail);
//        }

        return $trail;
    }

/* //TODO: Inutile dorénavant ?
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
*/
    public static function extractTrailName(Sentier $trail): string
//    public static function extractTrailName(Trail $trail): string
    {
        if ($trail->getDetails()) {
            $parts = explode('/', $trail->getDetails());
            return urldecode(end($parts));
        }

        throw new \Exception('missing trail name');
    }

    public static function getTrailLength(Sentier $trail): float
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
    public function getTrailsInsideBoundaries(Polygon $polygon, array $list): array
    {
        $trails = [];
        foreach ($list as $trail) {
            $coordinate = new Coordinate(array_values($trail->getStartPosition()));
            if ($polygon->pointInPolygon($coordinate)) {
                $trails[] = $trail;
            }
        }

        return $trails;
    }

    /**
     * Get full taxonomic infos, vernacular names, external links
     * TODO: encore utile?
     */
    /*
    public function collectOccurrencesTaxonInfos(Sentier $trail): void
//    public function collectOccurrencesTaxonInfos(Trail $trail): void
    {
        foreach ($trail->getOccurrences() as $occurrence) {
//            $taxon = $occurrence->getTaxo();
            $taxon = $occurrence->getTaxon();
            $taxon = $this->efloreService->getTaxon(
                $taxon->getReferentiel(), $taxon->getNumNom());
//            $occurrence->setTaxo($taxon);
            $occurrence->setTaxon($taxon);
        }
    }
*/
    //utilisé pour refresh les cards info
    public function buildOccurrencesTaxonInfos(Sentier $trail): void
    {
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxon();
            $taxon = $this->efloreService->getTaxon(
                $taxon['taxon_repository'], $taxon['name_id'], true);

            $json = $this->serializer->serialize($taxon, 'json', );
            $taxon = json_decode($json, true);

            $occurrence->setTaxon($taxon);
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
	
	public function getDraftTrailInfo($id){
        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
           return null;
        }

        if (!$trail->getDetails()){
            $trail->setDetails($this->router->generate('show_trail', ['id' => $trail->getNom() ], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return $trail;
	}

    //TODO: a updater
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
//			if ($trail){
//				$this->collectOccurrencesTaxonInfos($trail);
//				$this->collectTrailImages($trail);
//			}
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

        $trails = $this->sentierRepository->findBy(['status' => 'Validé', 'date_suppression' => null], ['nom' => 'ASC']);

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
//dd($trails);
        /**
         * @var $trail Sentier
//         * @var $trail Trail
         */
        foreach ($trails as $trail) {
//            $trailName = self::extractTrailName($trail);
            $trailName = $trail->getNom();

			try {
				$this->buildTrailCache($trailName);
				
				$trailCache = $this->cache->getItem('trails.trail.'.$trailName);
				$trail = $trailCache->get();
				if ($trail){
					$this->buildOccurrencesTaxonInfos($trail);
//					$this->imageService->buildTrailImagesCache($trail);
				}
			} catch (\Exception $e){
				print_r(' Erreur lors de la création du build trail cache du sentier: ');
				print_r($trailName);
				print_r(' '. $e->getMessage() . '/////');
				continue;
			}
        
        }
    }

    public function buildTrailCache(string $trailName)
    {
        $trailCache = $this->cache->getItem('trails.trail.'.$trailName);
        $trail = $this->sentierRepository->findOneBy(['nom' => $trailName, 'date_suppression' => null]);
        if (!$trail){
            throw new TrailNotFoundException('The trail'. $trailName .' does not exist');
        }

        if (!$trail->getDetails()){
            $trail->setDetails($this->router->generate('show_trail', [
                'id' => $trail->getNom()
            ], UrlGeneratorInterface::ABSOLUTE_URL));
        }
        $trailCache->set($trail);
        $this->cache->save($trailCache);
    }

    public function updateCacheTrailCards(array $trails){
        foreach ($trails as $trail) {
            try {
                $trailCache = $this->cache->getItem('trails.trail.' . $trail->getNom());
                $trail = $trailCache->get();
                if ($trail) {
                    $this->buildOccurrencesTaxonInfos($trail);
                }
            } catch (\Exception $e) {
                print_r(' Erreur lors de la maj du cache (cards)du sentier: ');
                print_r($trail->getNom(), $trail->getId());
                print_r(' ' . $e->getMessage() . '/////');
                continue;
            }
        }
    }
}
