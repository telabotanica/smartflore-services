<?php

namespace App\Service;

use App\Entity\Sentier;
//use App\Model\Image;
use App\Entity\Image;
use App\Model\Taxon;
use App\Model\Trail;
use App\Repository\SentierRepository;
use Symfony\Component\HttpFoundation\Request;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;
use League\Geotools\Polygon\Polygon;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    private SharedService $sharedService;
    private CacheFileService $cacheFile;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        string $userHashSecret,
        CacheInterface $cache,
        UrlGeneratorInterface $router,
        EfloreService $efloreService,
        SentierRepository $sentierRepository,
        ImageService $imageService,
        SerializerInterface $serializer,
        SharedService $sharedService,
        CacheFileService $cacheFile
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
        $this->sharedService = $sharedService;
        $this->cacheFile = $cacheFile;
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
            if ($taxon['taxon_repository'] & $taxon['name_id']) {
                $taxon = $this->efloreService->getTaxon(
                    $taxon['taxon_repository'], $taxon['name_id'], true);

                $json = $this->serializer->serialize($taxon, 'json', );
                $taxon = json_decode($json, true);
            }

            $occurrence->setTaxon($taxon);
        }
    }

    /**
     * Call private route for user's trails list (for /me route)
     */
//    public function getAllUserTrails($user): array
//    {
//        $trails = [];
//        $trails = $this->sentierRepository->findBy(['authorId' => $user->getId(), 'date_suppression' => null], ['nom' => 'ASC']);
//
//        if (!$trails){
//            $trails = $this->sentierRepository->findBy(['auteur_email' => $user->getEmail(), 'date_suppression' => null], ['nom' => 'ASC']);
//        }
//
//        return $trails;
//    }

    public function getAllUserTrails(User $user): array
    {
        $sentiers = $this->sentierRepository->findBy(
            ['authorId' => $user->getId(), 'date_suppression' => null],
            ['nom' => 'ASC']
        );

        if (!$sentiers) {
            $sentiers = $this->sentierRepository->findBy(
                ['auteur_email' => $user->getEmail(), 'date_suppression' => null],
                ['nom' => 'ASC']
            );
        }

        return array_map(fn(Sentier $sentier): Trail => $this->mapSentierToTrail($sentier), $sentiers);
    }

    private function mapSentierToTrail(Sentier $sentier): Trail
    {
        $trail = new Trail();
        $trail->setId($sentier->getId())
            ->setNom($sentier->getNom() ?? '')
            ->setDisplayName($sentier->getDisplayName() ?? '')
            ->setAuteur($sentier->getAuteur() ?? '')
            ->setAuthorId($sentier->getAuthorId() ?? '')
            ->setStatus($sentier->getStatus() ?? 'draft')
            ->setOccurrencesCount($sentier->getOccurrencesCount() ?? 0)
            ->setPathLength($sentier->getPathLength() ?? 0)
            ->setDetails($sentier->getDetails() ?? '');

//        if ($sentier->getPosition()) {
//            $trail->setPosition($sentier->getPosition());
//        }

        // Sentier::$image est un array JSON, Trail::$image attend un objet Image
        // On passe le tableau brut, à adapter selon ce que Trail::setImage() accepte
//        $imageData = $sentier->getImage();
//        if ($imageData && isset($imageData['url'])) {
//            $image = new Image();
//            $image->setUrl($imageData['url']);
//            if (isset($imageData['mini'])) {
//                $image->setMini($imageData['mini']);
//            }
//            $trail->setImage($image);
//        }

        return $trail;
    }
	
	public function getDraftTrailInfo($id){
        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
           return null;
        }

        if (!$trail->getDetails()){
            $this->sharedService->addDetailToTrail($trail);
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
            $this->sharedService->addDetailToTrail($trail);
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

    public function getSearchCriterias(Request $request){
        $criterias = [];
        $validSearchCriterias = ['nom', 'auteur', 'auteur_id', 'pmr', 'ordre', 'limite', 'page', 'status', 'show_deleted'];

        foreach ($validSearchCriterias as $criteria) {
            if ($request->query->has($criteria)) {
                $criterias[$criteria] = $request->query->get($criteria);
            }
        }

        return $criterias;
    }

    public function rebuildTrailsList(): void
    {
        $validatedTrails = $this->sentierRepository->findBy(
            ['status' => 'Validé', 'date_suppression' => null],
            ['nom' => 'ASC']
        );
        $this->cacheFile->saveTrailsList($validatedTrails, ['list_trail']);
    }
}
