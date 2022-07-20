<?php

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class TrailsApiMock extends MockHttpClient
{
    private static $baseUri = 'https://www.tela-botanica.org/smart-form/services/Sentiers.php/';

    public static function getResponses(): array
    {
        // longest URL first
        return [
            self::$baseUri.'sentier-illustration-fiche' => self::getTrailImagesMock(),
            self::$baseUri.'sentiers/REVE' => self::getTrailReveMock(),
            self::$baseUri.'sentiers/' => self::getTrailsListMock(),
        ];
    }

    private static function getTrailsListMock(): MockResponse
    {
        $mock = '[{"id":"146","nom":"Arbres Remarquables","auteur":"Denis N","position":[3.8732922077179,43.614253876832],"info":{"horaires":[],"gestionnaire":"","contact":"","site":"","logo":""},"photo":"","date_creation":"1464276564","date_modification":"1653316523","date_suppression":"","details":"https:\/\/www.tela-botanica.org\/smart-form\/services\/Sentiers.php\/sentiers\/REVE"},{"id":"5991","nom":"Jardin des Plantes Ville de Lille","auteur":"Yurbman","position":[3.0701389163733,50.615294809916],"info":{"horaires":[],"gestionnaire":"","contact":"","site":"","logo":""},"photo":"","date_creation":"1498139294","date_modification":"1499089353","date_suppression":"","details":"https:\/\/www.tela-botanica.org\/smart-form\/services\/Sentiers.php\/sentiers\/REVE"},{"id":"1668","nom":"REVE","auteur":"Ophelie C","position":[3.8169561177438,43.632495804283],"info":{"horaires":[],"gestionnaire":"","contact":"","site":"","logo":""},"photo":"","date_creation":"1464276564","date_modification":"1653402903","date_suppression":"","details":"https:\/\/www.tela-botanica.org\/smart-form\/services\/Sentiers.php\/sentiers\/REVE"}]';

        return new MockResponse(
            $mock,
            ['http_code' => Response::HTTP_OK]
        );
    }

    private static function getTrailReveMock(): MockResponse
    {
        $mock = '{"id":"1668","nom":"REVE","auteur":"Ophelie C","position":[3.8169561177438,43.632495804283],"info":{"horaires":[],"gestionnaire":"","contact":"","site":"","logo":""},"photo":"","date_creation":"1464276564","date_modification":"1653402903","date_suppression":"","occurrences":[{"position":[3.8170006871223,43.632320728333],"taxo":{"espece":"Pinus nigra subsp. salzmannii","auteur_espece":"(Dunal) Franco","auteur_genre":"","auteur_famille":"","genre":"Pinus","famille":"Pinaceae","referentiel":"bdtfx","num_nom":"49667"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-49667?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8168933987617,43.632361496387],"taxo":{"espece":"Pinus nigra subsp. salzmannii","auteur_espece":"(Dunal) Franco","auteur_genre":"","auteur_famille":"","genre":"Pinus","famille":"Pinaceae","referentiel":"bdtfx","num_nom":"49667"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-49667?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8168692588806,43.632503213693],"taxo":{"espece":"Juniperus communis","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Juniperus","famille":"Cupressaceae","referentiel":"bdtfx","num_nom":"36777"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-36777?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8155120611191,43.632497389701],"taxo":{"espece":"Quercus pubescens","auteur_espece":"Willd.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54438"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54438?sentier=REVE"},"infos":{"photo":""}},{"position":[3.815670311451,43.632477976389],"taxo":{"espece":"Tilia cordata","auteur_espece":"Mill.","auteur_genre":"","auteur_famille":"","genre":"Tilia","famille":"Malvaceae","referentiel":"bdtfx","num_nom":"68299"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-68299?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8155522942543,43.632584749526],"taxo":{"espece":"Ulmus minor","auteur_espece":"Mill.","auteur_genre":"","auteur_famille":"","genre":"Ulmus","famille":"Ulmaceae","referentiel":"bdtfx","num_nom":"70296"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-70296?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8154369592667,43.632565336242],"taxo":{"espece":"Acer monspessulanum","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Acer","famille":"Sapindaceae","referentiel":"bdtfx","num_nom":"182"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-182?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8153055310249,43.634329978087],"taxo":{"espece":"Quercus ilex","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54442"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54442?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8153377175331,43.634481397086],"taxo":{"espece":"Quercus coccifera","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54390"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54390?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8152089715004,43.634289211368],"taxo":{"espece":"Robinia pseudoacacia","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Robinia","famille":"Fabaceae","referentiel":"bdtfx","num_nom":"56245"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-56245?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8155549764633,43.634316389184],"taxo":{"espece":"Pinus halepensis","auteur_espece":"Mill.","auteur_genre":"","auteur_famille":"","genre":"Pinus","famille":"Pinaceae","referentiel":"bdtfx","num_nom":"75290"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-75290?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8152813911438,43.634425100323],"taxo":{"espece":"Eriobotrya japonica","auteur_espece":"(Thunb.) Lindl.","auteur_genre":"","auteur_famille":"","genre":"Eriobotrya","famille":"Rosaceae","referentiel":"bdtfx","num_nom":"24979"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-24979?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8154020905495,43.634395981287],"taxo":{"espece":"Cercis siliquastrum","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Cercis","famille":"Fabaceae","referentiel":"bdtfx","num_nom":"75048"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-75048?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8192000985146,43.633720415695],"taxo":{"espece":"Quercus pubescens","auteur_espece":"Willd.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54438"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54438?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8193556666374,43.633689355026],"taxo":{"espece":"Quercus ilex","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Quercus","famille":"Fagaceae","referentiel":"bdtfx","num_nom":"54442"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-54442?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8197070360184,43.633722356987],"taxo":{"espece":"Phillyrea angustifolia","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Phillyrea","famille":"Oleaceae","referentiel":"bdtfx","num_nom":"48852"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-48852?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8194951415062,43.633737887315],"taxo":{"espece":"Arbutus unedo","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Arbutus","famille":"Ericaceae","referentiel":"bdtfx","num_nom":"6055"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-6055?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8222900032997,43.634574577818],"taxo":{"espece":"Olea europaea","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Olea","famille":"Oleaceae","referentiel":"bdtfx","num_nom":"44593"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-44593?sentier=REVE"},"infos":{"photo":""}},{"position":[3.8221424818039,43.634481397086],"taxo":{"espece":"Ficus carica","auteur_espece":"L.","auteur_genre":"","auteur_famille":"","genre":"Ficus","famille":"Moraceae","referentiel":"bdtfx","num_nom":"75134"},"fiche":{"fr":"https:\/\/www.tela-botanica.org\/mobile:bdtfx-nn-75134?sentier=REVE"},"infos":{"photo":""}}],"chemin":{"type":"LineString","coordinates":[[3.8170182108661,43.63263172967],[3.8167714476367,43.633012228462],[3.8160418867847,43.633082115734],[3.8156878351947,43.632895749496],[3.815194308736,43.633392724846],[3.815194308736,43.633835340057],[3.8154518008014,43.634293482193],[3.8163959383746,43.634270186914],[3.8167070746204,43.634464313959],[3.8172220587512,43.634285717101],[3.8179301619311,43.634270186914],[3.8190137743732,43.63423136143],[3.8198077082416,43.634277952008],[3.8198613524219,43.633858635504],[3.8200973868152,43.634309012373],[3.8217496275684,43.634386663216],[3.8221251368304,43.634495374228]]}}';

        return new MockResponse(
            $mock,
            ['http_code' => Response::HTTP_OK]
        );
    }

    private static function getTrailImagesMock(): MockResponse
    {
        $mock = '{"SmartFloreTAXREFnt731626":{"illustrations":[{"id":2509237,"url":"https:\/\/api.tela-botanica.org\/img:002509237O","mini":"https:\/\/api.tela-botanica.org\/img:002509237CXS"},{"id":2509236,"url":"https:\/\/api.tela-botanica.org\/img:002509236O","mini":"https:\/\/api.tela-botanica.org\/img:002509236CXS"},{"id":2509235,"url":"https:\/\/api.tela-botanica.org\/img:002509235O","mini":"https:\/\/api.tela-botanica.org\/img:002509235CXS"}]}}';

        return new MockResponse(
            $mock,
            ['http_code' => Response::HTTP_OK]
        );
    }
}
