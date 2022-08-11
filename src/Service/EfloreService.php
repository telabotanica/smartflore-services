<?php

namespace App\Service;

use App\Model\Card;
use App\Model\Image;
use App\Model\Taxon;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\Cache\CacheInterface;

class EfloreService
{
    // vernacular names repositories indexed by taxon repository
    private const REFERENTIALS = [
        'bdtfx' => 'nvjfl',
        'bdtxa' => 'nva',
    ];

    private $client;
    private $cache;
    private $taxonApiBaseUrl;
    private $cardApiBaseUrl;
    private $imagesApiUrlTemplate;
    private $imageCosteApiUrlTemplate;
    private $vernacularNameApiUrlTemplate;

    public function __construct(
        string $taxonApiBaseUrl,
        string $cardApiBaseUrl,
        string $imagesApiUrlTemplate,
        string $imageCosteApiUrlTemplate,
        string $vernacularNameApiUrlTemplate,
        bool $useNativeHttpClient,
        CacheInterface $trailsCache
    ) {
        if ($useNativeHttpClient) {
            $this->client = new NativeHttpClient();
        } else {
            $this->client = HttpClient::create();
        }

        $this->cache = $trailsCache;
        $this->taxonApiBaseUrl = $taxonApiBaseUrl;
        $this->cardApiBaseUrl = $cardApiBaseUrl;
        $this->imagesApiUrlTemplate = $imagesApiUrlTemplate;
        $this->imageCosteApiUrlTemplate = $imageCosteApiUrlTemplate;
        $this->vernacularNameApiUrlTemplate = $vernacularNameApiUrlTemplate;
    }

    public function getTaxonRawInfo(string $taxonRepository, int $taxonNameId, bool $refresh = false)
    {
        $taxonCache = $this->cache->getItem('taxon.'.$taxonRepository.'.'.$taxonNameId);

        if ($refresh || !$taxonCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:eflore:0.1/taxref/taxons/125328
            $response = $this->client->request('GET',
                $this->taxonApiBaseUrl.$taxonRepository.'/taxons/'.$taxonNameId,
                ['timeout' => 120]
            );

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $taxon = json_decode($response->getContent(), true);

            $taxonCache->set($taxon);
            $this->cache->save($taxonCache);
        }

        return $taxonCache->get();
    }

    public function getCardText(string $taxonRepository, string $taxonId, bool $refresh = false)
    {
        $cardCache = $this->cache->getItem('taxon.card.SmartFlore'.strtoupper($taxonRepository).'nt'.$taxonId);

        if ($refresh || !$cardCache->isHit()) {
            // eg. https://www.tela-botanica.org/wikini/eFloreRedaction/api/rest/0.5/pages/SmartFloreBDTFXnt6293?txt.format=text/html&txt.section.titre=Description%2CUsages%2C%C3%89cologie+%26+habitat%2CSources
            $cardApiUrl = $this->cardApiBaseUrl.'SmartFlore'.strtoupper($taxonRepository).'nt'.$taxonId
                .'?txt.format=text/html&txt.section.titre='.urlencode('Description,Usages,Écologie & habitat,Sources');
            $response = $this->client->request('GET', $cardApiUrl, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $card = json_decode($response->getContent(), true);

            $cardCache->set($card);
            $this->cache->save($cardCache);
        }

        return $cardCache->get();
    }

    public function getCardSpeciesImages(string $taxonRepository, string $taxonNameId, bool $refresh = false, int $limit = 4)
    {
        $cardImagesCache = $this->cache->getItem('taxon.card.images.'.$taxonNameId);

        if ($refresh || !$cardImagesCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:del:0.1/images?navigation.depart=0&navigation.limite=4&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=CRS
            $imagesApiUrl = sprintf($this->imagesApiUrlTemplate, $limit, $taxonRepository, $taxonNameId);
            $response = $this->client->request('GET', $imagesApiUrl, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }

            $images = json_decode($response->getContent(), true)['resultats'];

            $res = [];
            foreach ($images as $image) {
                $res[] = new Image($image['id_image'], $image['binaire.href']);
            }

            $cardImagesCache->set($res);
            $this->cache->save($cardImagesCache);
        }

        $cardImages = $cardImagesCache->get();
        if (count($cardImages) > $limit) {
            $cardImages = array_slice($cardImages, 0, $limit);
        }

        return $cardImages;
    }

    public function getCardCosteImage(string $taxonRepository, string $taxonId, bool $refresh = false)
    {
        $cardImageCosteCache = $this->cache->getItem('taxon.card.images.coste.'.$taxonId);

        if ($refresh || !$cardImageCosteCache->isHit()) {
            $image = [];
            // only bdtfx taxa has Coste's image
            if ('bdtfx' === $taxonRepository) {
                // eg. https://api.tela-botanica.org/service:eflore:0.1/coste/images?masque.nt=29926&referentiel=bdtfx
                $imageCosteApiUrl = sprintf($this->imageCosteApiUrlTemplate, $taxonId, $taxonRepository);
                $response = $this->client->request('GET', $imageCosteApiUrl);

                if (200 !== $response->getStatusCode()) {
                    throw new \Exception('Response status code is different than expected.');
                }
                $image = json_decode($response->getContent(), true)['resultats'] ?? [];
                $image = reset($image) ?: [];
                if ($image) {
                    $image = new Image(0, $image['binaire.href']);
                }
            }

            $cardImageCosteCache->set($image);
            $this->cache->save($cardImageCosteCache);
        }

        return $cardImageCosteCache->get();
    }

    public function getVernacularName(string $taxonRepository, string $taxonId, bool $refresh = false)
    {
        $vernacularReferential = $this::REFERENTIALS[$taxonRepository] ?? null;
        $vernacularNameCache = $this->cache->getItem('taxon.vernacular.name.'.$taxonId);

        if ($refresh || !$vernacularNameCache->isHit()) {
            $vernacularNames = [];
            if ($vernacularReferential) {
                // eg. https://api.tela-botanica.org/service:eflore:0.1/nvjfl/noms-vernaculaires/?masque.nt=141&retour.champs=num_taxon,num_statut,code_langue&navigation.limite=99
                $vernacularNameApiUrl = sprintf($this->vernacularNameApiUrlTemplate,$vernacularReferential, $taxonId);
                $response = $this->client->request('GET', $vernacularNameApiUrl);

                if (200 !== $response->getStatusCode() && !(
                        404 === $response->getStatusCode()
                        && 'Les données recherchées sont introuvables.' === $response->getContent(false)
                    )) {
                    throw new \Exception('Response status code is different than expected.');
                }
                $vernacularNames = json_decode($response->getContent(false), true)['resultat'] ?? [];
            }

            $vernacularNameCache->set($vernacularNames);
            $this->cache->save($vernacularNameCache);
        }

        return $vernacularNameCache->get();
    }

    public function getTaxon(string $taxonRepository, string $taxonNameId, bool $refresh = false)
    {
        $taxonInfos = $this->getTaxonRawInfo(
            $taxonRepository, $taxonNameId, $refresh);

        $taxon = new Taxon();
        $taxon
            ->setEspece($taxonInfos['nom_sci'])
            ->setFullScientificName($taxonInfos['nom_complet'])
            ->setHtmlFullScientificName($taxonInfos['nom_sci_html_complet'])
            ->setGenre($taxonInfos['genre'] ?? '')
            ->setFamille($taxonInfos['famille'] ?? '')
            ->setReferentiel($taxonRepository)
            ->setNumNom($taxonInfos['id'])
            ->setAcceptedScientificNameId($taxonInfos['nom_retenu.id'])
            ->setTaxonomicId($taxonInfos['num_taxonomique'])
        ;

        // get the main species instead of subsp
        // maybe should use "nom retenu"?
        if (isset($taxonInfos['rang.libelle'], $taxonInfos['type_epithete']) && 'Espèce' !== $taxonInfos['rang.libelle']) {
            $taxon->setWikipediaUrl(
                'https://fr.wikipedia.org/wiki/'
                .str_replace(' ', '_',
                    mb_substr($taxonInfos['nom_sci_complet'], 0,
                        mb_strpos($taxonInfos['nom_sci_complet'], ' '.$taxonInfos['type_epithete'])))
            );
        }

        $taxon->setMapUrl(sprintf(
            'https://www.tela-botanica.org/widget:cel:cartoPoint?referentiel=%s&num_nom_ret=%s',
            $taxon->getReferentiel(),
            $taxon->getAcceptedScientificNameId()
        ));

        $images = $this->getCardSpeciesImages(
            $taxon->getReferentiel(), $taxon->getNumNom(), $refresh, 100);
        $taxon->setImages($images);

        $vernacularInfos = $this->getVernacularName(
            $taxon->getReferentiel(), $taxon->getTaxonomicId(), $refresh);
        foreach ($vernacularInfos as $vernacularInfo) {
            if ('fra' === ($vernacularInfo['code_langue'] ?? '')) {
                $taxon->addVernacularName($vernacularInfo['nom'], $vernacularInfo['num_statut'] ?? 0);
            }
        }

        $card = new Card();
        $cardSections = $this->getCardText($taxon->getReferentiel(), $taxon->getTaxonomicId(), $refresh);
        if (!isset($cardSections['sections'])) {
            $card->addSection('Fiche vide', 'Pas de contenu, cette fiche est vide.');
        } else {
            foreach ($cardSections['sections'] as $sectionTitle => $sectionText) {
                $card->addSection($sectionTitle, $sectionText);
            }
        }
        $taxon->setCard($card);

        return $taxon;
    }
}
