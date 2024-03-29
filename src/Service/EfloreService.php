<?php

namespace App\Service;

use App\Model\CardTab;
use App\Model\Image;
use App\Model\Referentiel;
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
            // eg. https://api.tela-botanica.org/service:del:0.1/images?navigation.depart=0&navigation.limite=100&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=M
            $imagesApiUrl = sprintf($this->imagesApiUrlTemplate, 100, $taxonRepository, $taxonNameId);
            $response = $this->client->request('GET', $imagesApiUrl, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }

            $images = json_decode($response->getContent(), true)['resultats'];

            $res = [];
            foreach ($images as $image) {
                $res[] = new Image($image['id_image'], $image['binaire.href'], $image['observation']['auteur.nom'] ?? 'Inconnu');
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
                    $image = new Image(0, $image['binaire.href'], 'Hippolyte Jacques Coste');
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

        $vernacularInfos = $this->getVernacularName(
            $taxon->getReferentiel(), $taxon->getTaxonomicId(), $refresh);
        foreach ($vernacularInfos as $vernacularInfo) {
            if ('fra' === ($vernacularInfo['code_langue'] ?? '')) {
                $taxon->addVernacularName($vernacularInfo['nom'], $vernacularInfo['num_statut'] ?? 0);
            }
        }

        $card = new CardTab();
        $card->setTitle('Fiche Smart’Flore')
            ->setType('card')
            ->setIcon('card');
        $cardSections = $this->getCardText($taxon->getReferentiel(), $taxon->getTaxonomicId(), $refresh);
        if (!isset($cardSections['sections'])) {
            $card->addSection('Fiche vide', 'Pas de contenu, cette fiche est vide.');
        } else {
            foreach ($cardSections['sections'] as $sectionTitle => $sectionText) {
                $card->addSection($sectionTitle, $sectionText);
            }
        }
        $images = $this->getCardSpeciesImages(
            $taxon->getReferentiel(), $taxon->getNumNom(), $refresh, 4);
        $card->setImages($images)->setImagesShort($images);
        $taxon->addTab($card);

        // gallery
        $gallery = new CardTab();
        $images = $this->getCardSpeciesImages(
            $taxon->getReferentiel(), $taxon->getNumNom(), $refresh, 100);
        $gallery->setTitle('Galerie')
            ->setType('gallery')
            ->setIcon('gallery')
            ->setImages($images)
            ->setImagesShort(array_slice($images, 0, 4))
        ;
        $taxon->addTab($gallery);

        // map
        $map = new CardTab();
        $mapUrl = sprintf(
            'https://www.tela-botanica.org/widget:cel:cartoPoint?referentiel=%s&num_nom_ret=%s',
            $taxon->getReferentiel(),
            $taxon->getAcceptedScientificNameId()
        );
        $map->setTitle('Carte de répartition')
            ->setType('webview')
            ->setIcon('map')
            ->setUrl($mapUrl);
        $taxon->addTab($map);

        // wikipedia
        $wikipedia = new CardTab();
        $wikipediaUrl = 'https://fr.wikipedia.org/wiki/'.str_replace (' ', '_', $taxon->getEspece());
        // for other rank than 'specie' (eg: subsp) we use specie name for wikipedia page (subsp pages are empty)
        if (isset($taxonInfos['rang.libelle'], $taxonInfos['type_epithete']) && 'Espèce' !== $taxonInfos['rang.libelle']) {
            $wikipediaUrl = 'https://fr.wikipedia.org/wiki/'
                .str_replace(' ', '_',
                    mb_substr($taxonInfos['nom_sci_complet'], 0,
                        mb_strpos($taxonInfos['nom_sci_complet'], ' '.$taxonInfos['type_epithete'])))
            ;
        }
        $wikipedia->setTitle('Wikipedia')
            ->setType('webview')
            ->setIcon('wikipedia')
            ->setUrl($wikipediaUrl);
        $taxon->addTab($wikipedia);

        return $taxon;
    }

    public function getTaxonRepositories(){
        $referentiels= [];

        $bdtfx = new Referentiel();
        $bdtfx->setNom("BDTFX");
        $bdtfx->setLabel("France métropolitaine");
        $bdtfx->setNomVernaculaire("nvjfl");
        $bdtfx->setFiltre(null);
        $bdtfx->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $bdtfx;

        $bdtxa = new Referentiel();
        $bdtxa->setNom("BDTXA");
        $bdtxa->setLabel("Antilles françaises");
        $bdtxa->setNomVernaculaire("nva");
        $bdtxa->setFiltre(null);
        $bdtxa->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $bdtxa;

        $isfan = new Referentiel();
        $isfan->setNom("ISFAN");
        $isfan->setLabel("Afrique du nord");
        $isfan->setNomVernaculaire(null);
        $isfan->setFiltre(null);
        $isfan->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $isfan;

        $apd = new Referentiel();
        $apd->setNom("APD");
        $apd->setLabel("Afrique du centre et de l'ouest");
        $apd->setNomVernaculaire(null);
        $apd->setFiltre(null);
        $apd->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $apd;

        $taxrefG = new Referentiel();
        $taxrefG->setNom("TAXREF");
        $taxrefG->setLabel("Guyane");
        $taxrefG->setNomVernaculaire(null);
        $taxrefG->setFiltre("guyane");
        $taxrefG->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $taxrefG;

        $taxrefR = new Referentiel();
        $taxrefR->setNom("TAXREF");
        $taxrefR->setLabel("La Réunion");
        $taxrefR->setNomVernaculaire(null);
        $taxrefR->setFiltre("reunion");
        $taxrefR->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $taxrefR;

        $taxrefL = new Referentiel();
        $taxrefL->setNom("TAXREFLICH");
        $taxrefL->setLabel("Lichens");
        $taxrefL->setNomVernaculaire(null);
        $taxrefL->setFiltre(null);
        $taxrefL->setFournisseurFichesEspeces("eflore");
        $referentiels[]= $taxrefL;

        return $referentiels;
    }
}
