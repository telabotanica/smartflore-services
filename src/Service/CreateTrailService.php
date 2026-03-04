<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Occurrence;
use App\Entity\Sentier;
use App\Model\CreateOccurrenceDto;
use App\Model\CreateTrailDto;
use App\Model\Taxon;
use App\Model\User;
use App\Repository\FicheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CreateTrailService
{
    private $client;
    private $smartfloreLegacyApiBaseUrl;
    private $efloreApiBaseUrl;
    private $infosTaxonsUrl;
    private $imageUrl;
    private $imageMiniatureUrl;
    private $ipApiV2Image;
    private $authorizeToken;
    private $annuaire;
    private EntityManagerInterface $em;
    private EfloreService $eflore;
    private SharedService $sharedService;
    private ImageService $imageService;
    private SerializerInterface $serializer;
    private FicheRepository $ficheRepository;
    private UrlGeneratorInterface $router;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        string $efloreApiBaseUrl,
        string $infosTaxonsUrl,
        string $imageUrl,
        string $imageMiniatureUrl,
        string $ipApiV2Image,
        AnnuaireService $annuaire,
        EntityManagerInterface $em,
        EfloreService $eflore,
        SharedService $sharedService,
        ImageService $imageService,
        FicheRepository $ficheRepository,
        UrlGeneratorInterface $router
    ) {
        /**
         * @var $client HttpClientInterface
         */
        $this->client = HttpClient::create();
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
        $this->efloreApiBaseUrl = $efloreApiBaseUrl;
        $this->infosTaxonsUrl = $infosTaxonsUrl;
        $this->imageUrl = $imageUrl;
        $this->imageMiniatureUrl = $imageMiniatureUrl;
        $this->ipApiV2Image = $ipApiV2Image;
        $this->annuaire = $annuaire;
        $this->em = $em;
        $this->eflore = $eflore;
        $this->sharedService = $sharedService;
        $this->imageService = $imageService;
        $this->ficheRepository = $ficheRepository;
        $this->router = $router;
    }

    public function process(Sentier $trail): Sentier
    {
        $this->createTrail($trail);

        if ($trail->getOccurrences()){
            foreach ($trail->getOccurrences() as $occurrence) {
                $this->getCardTag($occurrence);
                $occurrence->setUserId(($trail->getAuthorId()));
            }

            //			$this->addLocation($trail);
//			if ($this->isTrailEligible($trail)) {
//				$email = $this->annuaire->getUser($this->getAuth())->getEmail();
//				$this->submitTrailToReview($trail, $email);
//			}
        }
        $this->addNbTaxonsToTrail($trail);
        $this->imageService->findImageForTrail($trail);

        $this->em->persist($trail);
        $this->em->flush();

        $trail = $this->sharedService->addDetailToTrail($trail);

        $this->em->persist($trail);
        $this->em->flush();

        return $trail;
    }

    public function createTrail(Sentier $trail): void
    {
        $trailName = $trail->getNom();

        if (!$this->isTrailNameAvailable($trailName)) {
            $trailName = $this->addRandomIntegerSuffixToAlreadyUsedTrailNameUntilNameIsFreeThisMethodNameIsTooLong($trailName);
        }

        $user = $this->annuaire->getUserInfos($this->getAuth());
        $auteur = $user->getName() ? $user->getName() : $user->getEmail();

        $trail->setPathLength(round(TrailsService::getTrailLength($trail)));
        $trail->setAuteur($auteur);
        $trail->setNom($trailName);
        $trail->setAuthorId($user->getId());
        $trail->setAuteurEmail($user->getEmail());
        $trail->setDateCreation(new \DateTime());
    }

    public function addLocation(Sentier $trail): void
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

    public function submitTrailToReview(Sentier $trail, string $authorEmail): void
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

    public function isTrailEligible(Sentier $trail): array
    {
        $errors = [];
        if (!$this->checkMinimalOccurrences($trail)) {
            $errors['nb_occurrences'] = 'Le sentier doit avoir au moins 10 occurrences';
        }

        if (!$this->checkTrailLocalisation($trail)) {
            $errors['localisation'] = 'Le sentier doit avoir une localisation';
        }

        if (!$this->checkTrailPath($trail)) {
            $errors['path'] = 'Le sentier doit avoir un chemin tracé';
        }

        if (!$this->checkOccurrencesLocalisation($trail)) {
            $errors['occurrences_localisation'] = 'Toutes les occurrences doivent être localisées';
        }

        $emptyFiches = $this->checkEmptyFiches($trail);
        if ($emptyFiches) {
            $errors['fiches_incompletes'] = [];
            foreach ($emptyFiches as $fiche) {
                $errors['fiches_incompletes'][] =$fiche;
            }
        }

        return $errors;
    }

    public function getCardTag(Occurrence $occurrence): void
    {
        $taxonRepository = $occurrence->getTaxon()['taxon_repository'];
        $taxon = new Taxon();
        $taxonArray = [];
        try {
            //-espece: "Acer campestre"
            //  -fullScientificName: "Acer campestre L."
            //  -htmlFullScientificName: "<span class="sci"><span class="gen">Acer</span> <span class="sp">campestre</span></span> <span class="auteur">L.</span> [<span class="annee">1753</span>, <span class="biblio">Sp. Pl., 2 : 1055</span>]"
            //  -genre: "Acer"
            //  -famille: "Sapindaceae"
            //  -referentiel: "bdtfx"
            //  -numNom: 141
            //  -acceptedScientificNameId: 141
            //  -taxonomicId: 8522
            //  -vernacularNames: array:7 [
            //    1 => "Érable champêtre"
            //    2 => "Petit Érable"
            //    3 => "Acéraille"
            //    4 => "Auzerole"
            //    5 => "Azeraille"
            //    6 => "Bois de poule"
            //    7 => "Bois-chaud"
            //  ]
            //  -tabs: null
           $taxonInfos = $this->eflore->getTaxonRawInfo($taxonRepository, $occurrence->getTaxon()['name_id']);

            $taxon
                ->setEspece($taxonInfos['nom_sci'])
                ->setFullScientificName($taxonInfos['nom_complet'])
                ->setHtmlFullScientificName($taxonInfos['nom_sci_html_complet'] ?? '')
                ->setGenre($taxonInfos['genre'] ?? '')
                ->setFamille($taxonInfos['famille'] ?? '')
                ->setReferentiel($taxonRepository)
                ->setNumNom($taxonInfos['id'])
                ->setAcceptedScientificNameId($taxonInfos['nom_retenu.id'])
                ->setTaxonomicId($taxonInfos['num_taxonomique'])
            ;

            $vernacularInfos = $this->eflore->getVernacularName(
                $taxon->getReferentiel(), $taxon->getTaxonomicId());
            foreach ($vernacularInfos as $vernacularInfo) {
                if ('fra' === ($vernacularInfo['code_langue'] ?? '')) {
                    $taxon->addVernacularName($vernacularInfo['nom'], $vernacularInfo['num_statut'] ?? 0);
                }
            }

            $taxonArray = [
                'name_id' => $taxon->getNumNom() ?? null,
                'scientific_name' => $taxon->getFullScientificName() ?? null,
                'html_full_scientific_name' => $taxon->getHtmlFullScientificName() ?? null,
                'genus' => $taxon->getGenre() ?? null,
                'family' => $taxon->getFamille() ?? null,
                'taxon_repository' => $taxon->getReferentiel() ?? null,
                'accepted_scientific_name_id' => $taxon->getAcceptedScientificNameId() ?? null,
                'taxonomic_id' => $taxon->getTaxonomicId() ?? null,
                'vernacular_names' => $taxon->getVernacularNames() ?? []
            ];
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la récupération de la taxon.');
        }

        $fiche = $this->sharedService->chercherFiche($taxonRepository, $taxon->getTaxonomicId());

        if ($fiche) {
            $taxonArray['tabs'] = $fiche->getTag();
            $occurrence->setCardTag($fiche->getTag());
        }
        $occurrence->setTaxon($taxonArray);
    }

    public function isTrailNameAvailable(string $trailName): bool
    {
        $existingTrail = $this->em->getRepository(Sentier::class)->findBy(['nom' => $trailName, 'date_suppression' => null]);
        return !$existingTrail;
    }

    public function addRandomIntegerSuffixToAlreadyUsedTrailNameUntilNameIsFreeThisMethodNameIsTooLong(string $trailName): string
    {
        do {
            $trailName.=random_int(1,100);
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

    public function getUniqueCardTags(array $uniqueCardTags, Occurrence $occurrence): array
    {
        $cardTag = $occurrence->getCardTag();
        if ($cardTag && !in_array($cardTag, $uniqueCardTags, true)) {
            $uniqueCardTags[] = $cardTag;
        }
        return $uniqueCardTags;
    }

    public function getUniqueTaxons(Sentier $trail): array
    {
        $taxons = [];
        $seenKeys = [];

        foreach ($trail->getOccurrences() as $occurrence) {
            $taxonData = $occurrence->getTaxon();
            if (!$taxonData) {
                continue;
            }

            $key = ($taxonData['taxon_repository'] ?? '') . '_' . ($taxonData['name_id'] ?? '');
            if (!in_array($key, $seenKeys, true)) {
                $seenKeys[] = $key;
                $taxons[] = $taxonData;
            }
        }

        return $taxons;
    }

    public function setTaxonToOccurrence(Occurrence $occurrence, $content) {
        $taxon = new Taxon();
        if (isset($content->taxon)) {
            $taxon->setFullScientificName($content->taxon->scientific_name);
            $taxon->setReferentiel($content->taxon->taxon_repository);
            $taxon->setNumNom($content->taxon->name_id);
            unset($content->taxon);
        } elseif (isset($content->scientific_name) && isset($content->taxon_repository) && isset($content->name_id)) {
            $taxon->setFullScientificName($content->scientific_name);
            $taxon->setReferentiel($content->taxon_repository);
            $taxon->setNumNom($content->name_id);

            unset($content->scientific_name);
            unset($content->taxon_repository);
            unset($content->name_id);
        }
        $occurrence->setTaxon([
            'scientific_name' => $taxon->getFullScientificName(),
            'taxon_repository' => $taxon->getReferentiel(),
            'name_id' => $taxon->getNumNom()
        ]);

        return $occurrence;
    }

    public function setImagesToOccurrence(Occurrence $occurrence, $image_id) {
        $image = new Image();
        $image->setCelImageId($image_id);

        $image_api_id = str_pad($image_id, 9, '0', STR_PAD_LEFT);
        $image->setMini(sprintf($this->imageMiniatureUrl, $image_api_id));
        $image->setUrl(sprintf($this->imageUrl, $image_api_id));

        // On récupère les infos de l'image
        $response = $this->client->request('GET', sprintf($this->ipApiV2Image, $image_id), []);
        if (200 == $response->getStatusCode()) {
            $image_data = json_decode($response->getContent());
            $author = $image_data->observation->{'auteur.nom'};
            $image->setAuthor($author);
        }
//dd($image);
        $image->setOccurrence($occurrence);
        $occurrence->addImage($image);

        return $occurrence;
    }

    public function getImageFromContent($content): Image {
        $image = new Image();

        $image->setCelImageId($content->id);
        $image->setUrl($content->url);
        if (isset($content->author)) {
            $image->setAuthor($content->author);
        }

        return $image;
    }

    public function addNbTaxonsToTrail(Sentier $trail): void
    {
        $nb_taxons = 0;
        if ($trail->getOccurrences()) {
            $uniqueTaxons = $this->getUniqueTaxons($trail);
            $nb_taxons = count($uniqueTaxons);
        }

        $trail->setOccurrencesCount(count($trail->getOccurrences()));
        $trail->setNbTaxons($nb_taxons);
    }

    private function checkMinimalOccurrences(Sentier $trail): bool
    {
        $occurrences = $trail->getOccurrences();
        if (count($occurrences) < 10) {
            return false;
        }
        return true;
    }

    private function checkOccurrencesLocalisation(Sentier $trail): bool
    {
        $occurrences = $trail->getOccurrences();
        foreach ($occurrences as $occurrence) {
            if (!$occurrence->getPosition()) {
                return false;
            }
        }

        return true;
    }

    private function checkTrailLocalisation(Sentier $trail): bool
    {
        $position = $trail->getPosition();
        if (!$position) {
            return false;
        }
        return true;
    }

    private function checkTrailPath(Sentier $trail): bool
    {
        $path = $trail->getChemin();
        if (!$path) {
            return false;
        }
        return true;
    }

    private function checkEmptyFiches(Sentier $trail): array
    {
        $emptyFiches = [];
        foreach ($trail->getOccurrences() as $occurrence) {
            if (!$occurrence->getCardTag() ||
                strpos($occurrence->getCardTag(), 'SmartFlore') === false
            ) {
                //TODO: réparer le strpos
                $emptyFiches[] = [
                    'occurrence_id' => $occurrence->getId(),
                    'fiche_tag' => $occurrence->getCardTag(),
                    'taxon' => [
                        'scientific_name' => $occurrence->getTaxon()['scientific_name'],
                        'taxon_repository' => $occurrence->getTaxon()['taxon_repository'],
                        'name_id' => $occurrence->getTaxon()['name_id'],
                        'taxonomic_id' => $occurrence->getTaxon()['taxonomic_id']
                        ],
                    'error' => 'La fiche n\'existe pas et doit être créée puis remplie avec au moins une description et les sources'
                    ];
                continue;
            }

            $fiche = $this->ficheRepository->findOneBy(['tag' => $occurrence->getCardTag(), 'derniere_version' => 1]);
            if (!$fiche->getDescription() || !$fiche->getSources()) {
                $emptyFiches[] = [
                    'occurrence_id' => $occurrence->getId(),
                    'fiche_tag' => $occurrence->getCardTag(),
                    'taxon' => [
                        'scientific_name' => $occurrence->getTaxon()['scientific_name'],
                        'taxon_repository' => $occurrence->getTaxon()['taxon_repository'],
                        'name_id' => $occurrence->getTaxon()['name_id'],
                        'taxonomic_id' => $occurrence->getTaxon()['taxonomic_id']
                    ],
                    'error' => 'La fiche doit être remplie avec au moins une description et les sources'
                ];
            }
        }

        return $emptyFiches;
    }
}
