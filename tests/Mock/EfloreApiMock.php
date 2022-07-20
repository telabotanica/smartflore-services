<?php

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class EfloreApiMock extends MockHttpClient
{
    private static $baseUri = 'https://api.tela-botanica.org/service:';

    public static function getResponses(): array
    {
        // longest URL first
        return [
            self::$baseUri.'eflore:0.1/coste/images' => self::getCosteMock(),
            self::$baseUri.'eflore' => self::getEfloreMock(),
            self::$baseUri.'del' => self::getDelMock(),
        ];
    }

    private static function getCosteMock(): MockResponse
    {
        $mock = '{"resultats":{"2469":{"num_nomenclatural":"16357","num_taxonomique":"141","binaire.href":"https:\/\/api.tela-botanica.org\/donnees\/coste\/2.00\/img\/1630.png","mime":"images\/png"}}}';

        return new MockResponse(
            $mock,
            ['http_code' => Response::HTTP_OK]
        );
    }

    private static function getEfloreMock(): MockResponse
    {
        $mock = '{"id":"141","nom_sci":"Acer campestre","nom_sci_complet":"Acer campestre L. [1753, Sp. Pl., 2 : 1055]","nom_retenu.id":"141","nom_retenu.libelle":"Acer campestre","nom_retenu_html":"<span class=\"sci\"><span class=\"gen\">Acer<\/span> <span class=\"sp\">campestre<\/span><\/span>","nom_retenu_complet":"Acer campestre L. [1753, Sp. Pl., 2 : 1055]","nom_retenu_html_complet":"<span class=\"sci\"><span class=\"gen\">Acer<\/span> <span class=\"sp\">campestre<\/span><\/span> <span class=\"auteur\">L.<\/span> [<span class=\"annee\">1753<\/span>, <span class=\"biblio\">Sp. Pl., 2 : 1055<\/span>]","nom_retenu.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdtfx\/taxons\/141","tax_sup.id":"83587","tax_sup.libelle":"Acer","tax_sup_html":"<span class=\"sci\"><span class=\"gen\">Acer<\/span><\/span>","tax_sup_complet":"Acer L. [1753, Sp. Pl., 2 : 1054]","tax_sup_html_complet":"<span class=\"sci\"><span class=\"gen\">Acer<\/span><\/span> <span class=\"auteur\">L.<\/span> [<span class=\"annee\">1753<\/span>, <span class=\"biblio\">Sp. Pl., 2 : 1054<\/span>]","tax_sup.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdtfx\/taxons\/83587","rang.code":"bdnt.rangTaxo:290","rang.libelle":"Esp\u00e8ce","rang.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdnt\/ontologies\/rangTaxo:290","genre":"Acer","epithete_sp":"campestre","auteur":"L.","annee":"1753","biblio_origine":"Sp. Pl., 2 : 1055","num_type":"141","basionyme.id":"141","basionyme.libelle":"Acer campestre","basionyme_html":"<span class=\"sci\"><span class=\"gen\">Acer<\/span> <span class=\"sp\">campestre<\/span><\/span>","basionyme_complet":"Acer campestre L. [1753, Sp. Pl., 2 : 1055]","basionyme_html_complet":"<span class=\"sci\"><span class=\"gen\">Acer<\/span> <span class=\"sp\">campestre<\/span><\/span> <span class=\"auteur\">L.<\/span> [<span class=\"annee\">1753<\/span>, <span class=\"biblio\">Sp. Pl., 2 : 1055<\/span>]","basionyme.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdtfx\/taxons\/141","nom_fr":"Ac\u00e9raille","presence.code":"bdnt.presence:P","presence.libelle":"Pr\u00e9sent","presence.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdnt\/ontologies\/presence:P","statut_origine.code":"bdnt.statutOrigine:N","statut_origine.libelle":"Natif (=indig\u00e8ne)","statut_origine.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdnt\/ontologies\/statutOrigine:N","statut_introduction.code":"bdnt.statutIntroduction:A","statut_introduction.libelle":"Non introduit","statut_introduction.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdnt\/ontologies\/statutIntroduction:A","presence_Ga.code":"bdnt.presence:P","presence_Ga.libelle":"Pr\u00e9sent","presence_Ga.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdnt\/ontologies\/presence:P","presence_Co.code":"bdnt.presence:P","presence_Co.libelle":"Pr\u00e9sent","presence_Co.href":"https:\/\/api.tela-botanica.org\/service:eflore:0.1\/bdnt\/ontologies\/presence:P","exclure_taxref":"0","num_taxonomique":"8522","statut":"1","nom_complet":"Acer campestre L.","flores":"1, 2, 3, 4, 5, 6","maj_modif":"24\/01\/2021","classification":"4","2n":"26","flore_cnrs_num":"0716","flore_coste_num":"0693","flore_fh_num":"1363","flore_fournier_num":"2729","num_meme_type":"141","flore_belge_ed5_page":"0454 R","auteur_principal":"L.","cd_nom":"79734","flore_fg_num":"1041r","source_biblio":"http:\/\/www.biodiversitylibrary.org\/item\/13830#page\/497\/mode\/1up","famille":"Sapindaceae","nom_sci_html":"<span class=\"sci\"><span class=\"gen\">Acer<\/span> <span class=\"sp\">campestre<\/span><\/span>","nom_sci_html_complet":"<span class=\"sci\"><span class=\"gen\">Acer<\/span> <span class=\"sp\">campestre<\/span><\/span> <span class=\"auteur\">L.<\/span> [<span class=\"annee\">1753<\/span>, <span class=\"biblio\">Sp. Pl., 2 : 1055<\/span>]","hierarchie":"-0-101140-102735-102762-102768-101137-87491-87521-102795-101135-101004-101029-101053-102751-102750-101012-83587-"}';

        return new MockResponse(
            $mock,
            ['http_code' => Response::HTTP_OK]
        );
    }

    private static function getDelMock(): MockResponse
    {
        $mock = '{"entete":{"masque":"navigation.depart=0&navigation.limite=4&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=O","total":513,"depart":0,"limite":4,"href.suivant":"https:\/\/api.tela-botanica.org\/service:del:0.1\/images?navigation.depart=4&navigation.limite=4&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=O"},"resultats":[{"id_image":"2501516","binaire.href":"https:\/\/api.tela-botanica.org\/img:002501516O.jpg","mots_cles_texte":null,"observation":{"id_observation":"3803291","date_observation":"2021-09-23 12:00:00","date_transmission":"2021-09-24 09:12:17","determination.famille":"Sapindaceae","determination.ns":"Acer platanoides L. [1753]","determination.nn":"74934","determination.nt":"74934","determination.referentiel":"bdtfx","id_zone_geo":"34217","zone_geo":"Saint-Cl\u00e9ment-de-Rivi\u00e8re","lieudit":"France, H\u00e9rault, Prades-le-Lez, ripisylve du Lez (58 m)","station":null,"milieu":"ripisylve","commentaire":null,"auteur.id":"24769","auteur.nom":"Sylvain Piry","auteur.prenom":null,"auteur.courriel":"sylvain.piry@inrae.fr","pays":"FR","hauteur":"2400","date":"2021-09-23 18:42:34","nom_original":"P9237322.JPG"},"protocoles_votes":[]},{"id_image":"2501517","binaire.href":"https:\/\/api.tela-botanica.org\/img:002501517O.jpg","mots_cles_texte":null,"observation":{"id_observation":"3803291","date_observation":"2021-09-23 12:00:00","date_transmission":"2021-09-24 09:12:17","determination.famille":"Sapindaceae","determination.ns":"Acer platanoides L. [1753]","determination.nn":"74934","determination.nt":"74934","determination.referentiel":"bdtfx","id_zone_geo":"34217","zone_geo":"Saint-Cl\u00e9ment-de-Rivi\u00e8re","lieudit":"France, H\u00e9rault, Prades-le-Lez, ripisylve du Lez (58 m)","station":null,"milieu":"ripisylve","commentaire":null,"auteur.id":"24769","auteur.nom":"Sylvain Piry","auteur.prenom":null,"auteur.courriel":"sylvain.piry@inrae.fr","pays":"FR","hauteur":"2400","date":"2021-09-23 18:42:39","nom_original":"P9237323.JPG"},"protocoles_votes":[]},{"id_image":"2501518","binaire.href":"https:\/\/api.tela-botanica.org\/img:002501518O.jpg","mots_cles_texte":null,"observation":{"id_observation":"3803291","date_observation":"2021-09-23 12:00:00","date_transmission":"2021-09-24 09:12:17","determination.famille":"Sapindaceae","determination.ns":"Acer platanoides L. [1753]","determination.nn":"74934","determination.nt":"74934","determination.referentiel":"bdtfx","id_zone_geo":"34217","zone_geo":"Saint-Cl\u00e9ment-de-Rivi\u00e8re","lieudit":"France, H\u00e9rault, Prades-le-Lez, ripisylve du Lez (58 m)","station":null,"milieu":"ripisylve","commentaire":null,"auteur.id":"24769","auteur.nom":"Sylvain Piry","auteur.prenom":null,"auteur.courriel":"sylvain.piry@inrae.fr","pays":"FR","hauteur":"2400","date":"2021-09-23 18:42:58","nom_original":"P9237325.JPG"},"protocoles_votes":[]},{"id_image":"2501519","binaire.href":"https:\/\/api.tela-botanica.org\/img:002501519O.jpg","mots_cles_texte":null,"observation":{"id_observation":"3803291","date_observation":"2021-09-23 12:00:00","date_transmission":"2021-09-24 09:12:17","determination.famille":"Sapindaceae","determination.ns":"Acer platanoides L. [1753]","determination.nn":"74934","determination.nt":"74934","determination.referentiel":"bdtfx","id_zone_geo":"34217","zone_geo":"Saint-Cl\u00e9ment-de-Rivi\u00e8re","lieudit":"France, H\u00e9rault, Prades-le-Lez, ripisylve du Lez (58 m)","station":null,"milieu":"ripisylve","commentaire":null,"auteur.id":"24769","auteur.nom":"Sylvain Piry","auteur.prenom":null,"auteur.courriel":"sylvain.piry@inrae.fr","pays":"FR","hauteur":"2400","date":"2021-09-23 18:41:37","nom_original":"P9237320.JPG"},"protocoles_votes":[]}]}';

        return new MockResponse(
            $mock,
            ['http_code' => Response::HTTP_OK]
        );
    }
}
