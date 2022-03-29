<?php

namespace App\Controller;

use App\Service\EfloreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CardController extends AbstractController
{
    /**
     * @Route("/fiche/text/{taxonRepo}/{taxonNameId}", name="sentier_list")
     */
    public function cardText(EfloreService $eflore, string $taxonRepo, string $taxonNameId)
    {
        $nt = $eflore->getTaxonInfo($taxonRepo, $taxonNameId)->num_taxonomique;
        return $this->json($eflore->getCardText($taxonRepo, $nt));
    }

    /**
     * @Route("/fiche/images/{taxonRepo}/{taxonNameId}", name="sentier_list")
     */
    public function cardImages(EfloreService $eflore, string $taxonRepo, string $taxonNameId)
    {
        // https://www.tela-botanica.org/smart-form/services/Sentiers.php/sentier-illustration-fiche/?sentierTitre=Sentier%20botanique%20de%20la%20r%C3%A9serve%20naturelle%20Tr%C3%A9sor&ficheTag=SmartFloreTAXREFnt731626
        // https://api-test.tela-botanica.org/service:eflore:0.1/coste/images?masque.nt=29926&referentiel=bdtfx
        // https://api.tela-botanica.org/service:del:0.1/images?navigation.depart=0&navigation.limite=4&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=CRS
//        $nt = $eflore->getTaxonInfo($taxonRepo, $taxonNameId)->num_taxonomique;
//        return $this->json($eflore->getCardText($taxonRepo, $nt));
    }
}
