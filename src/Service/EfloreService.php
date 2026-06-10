<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;
use App\Entity\Fiche;
use App\Model\CardTab;
use App\Entity\Image;
//use App\Model\Image;
use App\Model\Referentiel;
use App\Model\Taxon;
use App\Service\SharedService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class EfloreService
{
    // vernacular names repositories indexed by taxon repository
    private const REFERENTIALS = [
        'bdtfx' => 'nvjfl',
        'bdtxa' => 'nva',
    ];

    private HttpClientInterface $client;

    public function __construct(
        private readonly string           $taxonApiBaseUrl,
        private readonly string           $cardApiBaseUrl,
        private readonly string           $imagesApiUrlTemplate,
        private readonly string           $imageCosteApiUrlTemplate,
        private readonly string           $vernacularNameApiUrlTemplate,
        bool             $useNativeHttpClient,
        private readonly string           $rechercheNomsVernaEfloreUrl,
        private readonly string           $rechercheNomUrl,
        private readonly string           $infosTaxonsUrl,
        private readonly string           $smartflorefronturl,
        private readonly SharedService    $sharedService,
        private readonly CacheFileService $cacheFile,
        private readonly CacheInterface   $cache, private readonly SerializerInterface $serializer
    ) {
        if ($useNativeHttpClient) {
            $this->client = new NativeHttpClient();
        } else {
            $this->client = HttpClient::create();
        }
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
                throw new Exception('Response status code is different than expected.');
            }
            $taxon = json_decode((string) $response->getContent(), true);

            $taxonCache->set($taxon);
            $this->cache->save($taxonCache);

            return $taxon;
        }

        return $taxonCache->get();
    }

    public function getCardText(string $taxonRepository, int $taxonId, int $taxon_num_nom = null,  bool $refresh = false)
    {
        $cardCache = $this->cache->getItem('taxon.card.SmartFlore'.strtoupper($taxonRepository).'nt'.$taxonId);

        $cached = $this->cacheFile->getFiche($taxonRepository, $taxonId); //For Smarflore v2
        if ($cached) {
            $fiche = $this->serializer->deserialize(json_encode($cached), Fiche::class, 'json');
            $card['id'] = $fiche->getId();
            $card['titre'] = $fiche->getTag();
            //TODO: voir comment ajouter le num nom du taxon
            $card['href'] = $this->smartflorefronturl . "/fiche/" . $fiche->getReferentiel() . "/" . $fiche->getNt() ."/" . $taxon_num_nom;
            $card['sections']['description'] = $fiche->getDescription();
            $card['sections']['usages'] = $fiche->getUsages();
            $card['sections']['ecologie'] = $fiche->getEcologie();
            $card['sections']['sources'] = $fiche->getSources();

            return $card;
        }

        if ($refresh || !$cached || !$cardCache->isHit() ) {
            $fiche = $this->sharedService->chercherFiche($taxonRepository, $taxonId);

            if (!$fiche) {
                throw new Exception('No page found for taxon '.$taxonId.' in referentiel '.$taxonRepository);
            }

            $card['id'] = $fiche->getId();
            $card['titre'] = $fiche->getTag();
            $card['href'] = 'https://www.tela-botanica.org/wikini/eFloreRedaction/wakka.php?wiki=' . $fiche->getTag();
            $card['sections']['description'] = $fiche->getDescription();
            $card['sections']['usages'] = $fiche->getUsages();
            $card['sections']['ecologie'] = $fiche->getEcologie();
            $card['sections']['sources'] = $fiche->getSources();

            $cardCache->set($card);
            $this->cache->save($cardCache);
        }

        return $cardCache->get();
    }

    public function getCardSpeciesImages(string $taxonRepository, int $taxonNameId, bool $refresh = false, int $limit = 4)
    {
        $cardImagesCache = $this->cache->getItem('taxon.card.images.'.$taxonNameId);

        if ($refresh || !$cardImagesCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:del:0.1/images?navigation.depart=0&navigation.limite=100&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=M
            $imagesApiUrl = sprintf($this->imagesApiUrlTemplate, 100, $taxonRepository, $taxonNameId);
            $response = $this->client->request('GET', $imagesApiUrl, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new Exception('Response status code is different than expected.');
            }

            $images = json_decode((string) $response->getContent(), true)['resultats'];

            $res = [];
            foreach ($images as $image) {
                $newImae = new Image();
                $newImae->setCelImageId($image['id_image']);
                $newImae->setUrl($image['binaire.href']);
                $newImae->setAuthor($image['observation']['auteur.nom'] ?? 'Inconnu');
                $res[] = $newImae;
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
                    throw new Exception('Response status code is different than expected.');
                }
                $image = json_decode((string) $response->getContent(), true)['resultats'] ?? [];
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

    public function getVernacularName(string $taxonRepository, int $taxonId, bool $refresh = false)
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
                    throw new Exception('Response status code is different than expected.');
                }
                $vernacularNames = json_decode((string) $response->getContent(false), true)['resultat'] ?? [];
            }

            $vernacularNameCache->set($vernacularNames);
            $this->cache->save($vernacularNameCache);
        }

        return $vernacularNameCache->get();
    }

    public function consulterRechercheNomsVernaEflore(array $filtres) {
        $vernacularReferential = $this::REFERENTIALS[$filtres['referentiel']] ?? null;
        $vernacularNames = [];

        $url_eflore_verna_tpl = $this->taxonApiBaseUrl . $this->rechercheNomsVernaEfloreUrl;

        if ($vernacularReferential) {
            // eg. https://api.tela-botanica.org/service:eflore:0.1/nvjfl/noms-vernaculaires?masque=erable%25&recherche=etendue&retour.champs=num_taxon&masque.lg=fra&navigation.depart=10&navigation.limite=10
            $vernacularNameApiUrl = sprintf($url_eflore_verna_tpl, $vernacularReferential, urlencode($filtres['recherche'].'%'), $filtres['debut'], $filtres['limite']);

            $response = $this->client->request('GET', $vernacularNameApiUrl);

            if (200 !== $response->getStatusCode() && !(
                    404 === $response->getStatusCode()
                    && 'Les données recherchées sont introuvables.' === $response->getContent(false)
                )) {
                throw new Exception('Response status code is different than expected.');
            }
            $vernacularNames = json_decode((string) $response->getContent(false), true) ?? [];
        }

        return $vernacularNames;
    }

    public function consulterRechercheNomsSciEflore(array $filtres) {
        $url_eflore_tpl = $this->taxonApiBaseUrl . $this->rechercheNomUrl;
        $url = sprintf($url_eflore_tpl , strtolower((string) $filtres['referentiel']), 'etendue', urlencode($filtres['recherche'].'%'), $filtres['debut'], $filtres['limite']);

        if (isset($filtres['filtre'])) {
            $url .= '&masque.ref='.$filtres['filtre'];
        }

        $response = $this->client->request('GET', $url);

        if (200 !== $response->getStatusCode() && !(
                404 === $response->getStatusCode()
                && 'Les données recherchées sont introuvables.' === $response->getContent(false)
            )) {
            throw new Exception('Response status code is different than expected.');
        }

        $infos = json_decode((string) $response->getContent(false), true) ?? [];

        if (empty($infos)){
            $url = sprintf($url_eflore_tpl, strtolower((string) $filtres['referentiel']), 'floue', urlencode($filtres['recherche'].'%'), $filtres['debut'], $filtres['limite']);
            $response = $this->client->request('GET', $url);
            if (200 !== $response->getStatusCode() && !(
                    404 === $response->getStatusCode()
                    && 'Les données recherchées sont introuvables.' === $response->getContent(false)
                )) {
                throw new Exception('Response status code is different than expected.');
            }

            $infos = json_decode((string) $response->getContent(false), true) ?? [];
        }

        return $infos;
    }

    public function getInfosTaxons($referentiel, $num_tax): array
    {
        $url_eflore_tpl = $this->taxonApiBaseUrl . $this->infosTaxonsUrl;
        $url = sprintf($url_eflore_tpl, strtolower((string) $referentiel), $num_tax);
        $response = $this->client->request('GET', $url);
        if (200 !== $response->getStatusCode() && !(
                404 === $response->getStatusCode()
                && 'Les données recherchées sont introuvables.' === $response->getContent(false)
            )) {
            throw new Exception('Response status code is different than expected.');
        }

        return json_decode((string) $response->getContent(false), true) ?? [];
    }

    public function getTaxon(string $taxonRepository, int $taxonNameId, bool $refresh = false): ?Taxon
    {
        try {
            $taxonInfos = $this->getTaxonRawInfo(
                $taxonRepository, $taxonNameId, $refresh);
        } catch (Exception) {
            return null;
        }

        $taxon = new Taxon();
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
        $cardSections = $this->getCardText($taxon->getReferentiel(), $taxon->getTaxonomicId(), $taxon->getNumNom(), $refresh);

        if (!isset($cardSections['sections'])) {
            //TO update ?
            $card->addSection('Fiche vide', 'Pas de contenu, cette fiche est vide.');
        } else {
            foreach ($cardSections['sections'] as $sectionTitle => $sectionText) {
                if ($sectionText && $sectionTitle) {
                    $card->addSection($sectionTitle, $sectionText);
                }
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
                    mb_substr((string) $taxonInfos['nom_sci_complet'], 0,
                        mb_strpos((string) $taxonInfos['nom_sci_complet'], ' '.$taxonInfos['type_epithete'])))
            ;
        }
        $wikipedia->setTitle('Wikipedia')
            ->setType('webview')
            ->setIcon('wikipedia')
            ->setUrl($wikipediaUrl);
        $taxon->addTab($wikipedia);

        return $taxon;
    }

    public function getTaxonRepositories(): array{
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
