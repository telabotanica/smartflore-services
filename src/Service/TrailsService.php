<?php

namespace App\Service;

use App\Model\Trail;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;

class TrailsService
{
    private $client;
    private $cache;
    private $smartfloreLegacyApiBaseUrl;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        CacheInterface $trailsCache
    ) {
        $this->client = HttpClient::create();
        $this->cache = $trailsCache;
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
    }

    public function getTrails(bool $refresh = false)
    {
        $trailsCache = $this->cache->getItem('trails.list');

        if ($refresh || !$trailsCache->isHit()) {
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl, [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }

//            $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
//            $normalizer = [
//                new ArrayDenormalizer(),
//                new PropertyNormalizer(),
//                new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, $extractor),
//            ];
//            $serializer = new Serializer($normalizer, [new JsonEncoder()]);
//
//            $data = $response->getContent();
//
//            $trails = $serializer->deserialize($data, 'App\Model\Trail[]', 'json');

            $trails = json_decode($response->getContent());

            $trailsCache->set($trails);
            $this->cache->save($trailsCache);
        }

        return $trailsCache->get();
    }

    public function getTrail(string $trailName) {
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $normalizer = [
            new ArrayDenormalizer(),
            new PropertyNormalizer(),
            new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, $extractor),
        ];
        $serializer = new Serializer($normalizer, [new JsonEncoder()]);

        $data = <<<DATA
{"id":"1668","nom":"REVE","auteur":"Ophelie Caille","position":[3.8169561177438,43.632495804283],"info":{"horaires":[],"gestionnaire":"","contact":"","site":"","logo":""},"photo":"","date_creation":"1464276564","date_modification":"1504001220","date_suppression":"","occurrences":[{"position":[3.8170006871223,43.632320728333],"taxo":{"espece":"Pinus nigra subsp. salzmannii","auteur_espece":"(Dunal) Franco","auteur_genre":"","auteur_famille":"","genre":"Pinus","famille":"Pinaceae","referentiel":"bdtfx","num_nom":"49667"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-49667?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8168933987617,43.632361496387],"taxo":{"espece":"Pinus nigra subsp. salzmannii","auteur_espece":"(Dunal) Franco","auteur_genre":"","auteur_famille":"","genre":"Pinus","famille":"Pinaceae","referentiel":"bdtfx","num_nom":"49667"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-49667?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8168692588806,43.632503213693],"taxo":{"espece":"Juniperus communis","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Juniperus","famille":"Cupressaceae","referentiel":"bdtfx","num_nom":"36777"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-36777?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8155120611191,43.632497389701],"taxo":{"espece":"Quercus pubescens","auteur_espece":"Willd.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54438"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54438?sentier=REVE"},"infos":{"photo":""}},{"position":[3.815670311451,43.632477976389],"taxo":{"espece":"Tilia cordata","auteur_espece":"Mill.","auteur_genre":"","auteur_famille":"","genre":"Tilia","famille":"Malvaceae","referentiel":"bdtfx","num_nom":"68299"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-68299?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8155522942543,43.632584749526],"taxo":{"espece":"Ulmus minor","auteur_espece":"Mill.","auteur_genre":"","auteur_famille":"","genre":"Ulmus","famille":"Ulmaceae","referentiel":"bdtfx","num_nom":"70296"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-70296?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8154369592667,43.632565336242],"taxo":{"espece":"Acer monspessulanum","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Acer","famille":"Sapindaceae","referentiel":"bdtfx","num_nom":"182"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-182?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8153055310249,43.634329978087],"taxo":{"espece":"Quercus ilex","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54442"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54442?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8153377175331,43.634481397086],"taxo":{"espece":"Quercus coccifera","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54390"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54390?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8152089715004,43.634289211368],"taxo":{"espece":"Robinia pseudoacacia","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Robinia","famille":"Fabaceae","referentiel":"bdtfx","num_nom":"56245"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-56245?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8155549764633,43.634316389184],"taxo":{"espece":"Pinus halepensis","auteur_espece":"Mill.","auteur_genre":"","auteur_famille":"","genre":"Pinus","famille":"Pinaceae","referentiel":"bdtfx","num_nom":"75290"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-75290?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8152813911438,43.634425100323],"taxo":{"espece":"Eriobotrya japonica","auteur_espece":"(Thunb.) Lindl.","auteur_genre":"","auteur_famille":"","genre":"Eriobotrya","famille":"Rosaceae","referentiel":"bdtfx","num_nom":"24979"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-24979?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8154020905495,43.634395981287],"taxo":{"espece":"Cercis siliquastrum","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Cercis","famille":"Fabaceae","referentiel":"bdtfx","num_nom":"75048"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-75048?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8192000985146,43.633720415695],"taxo":{"espece":"Quercus pubescens","auteur_espece":"Willd.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54438"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54438?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8193556666374,43.633689355026],"taxo":{"espece":"Quercus ilex","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54442"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54442?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8197070360184,43.633722356987],"taxo":{"espece":"Phillyrea angustifolia","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Phillyrea","famille":"Oleaceae","referentiel":"bdtfx","num_nom":"48852"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-48852?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8194951415062,43.633737887315],"taxo":{"espece":"Arbutus unedo","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Arbutus","famille":"Ericaceae","referentiel":"bdtfx","num_nom":"6055"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-6055?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8222900032997,43.634574577818],"taxo":{"espece":"Olea europaea","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Olea","famille":"Oleaceae","referentiel":"bdtfx","num_nom":"44593"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-44593?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8221424818039,43.634481397086],"taxo":{"espece":"Ficus carica","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Ficus","famille":"Moraceae","referentiel":"bdtfx","num_nom":"75134"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-75134?sentier=REVE"},"infos":{"photo":""}}],"chemin":{"type":"LineString","coordinates":[[3.8170182108661,43.63263172967],[3.8167714476367,43.633012228462],[3.8160418867847,43.633082115734],[3.8156878351947,43.632895749496],[3.815194308736,43.633392724846],[3.815194308736,43.633835340057],[3.8154518008014,43.634293482193],[3.8163959383746,43.634270186914],[3.8167070746204,43.634464313959],[3.8172220587512,43.634285717101],[3.8179301619311,43.634270186914],[3.8190137743732,43.63423136143],[3.8198077082416,43.634277952008],[3.8198613524219,43.633858635504],[3.8200973868152,43.634309012373],[3.8217496275684,43.634386663216],[3.8221251368304,43.634495374228]]}}
DATA;
        $trails = $serializer->deserialize($data, Trail::class, 'json', ['disable_type_enforcement' => true]);

//        die(var_dump($trails));

        return $trails;

    }
}
