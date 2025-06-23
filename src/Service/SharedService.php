<?php

namespace App\Service;

use App\Entity\Fiche;
use App\Repository\FicheRepository;

class SharedService
{
    private FicheRepository $ficheRepository;

    public function __construct(FicheRepository $ficheRepository)
    {
        $this->ficheRepository = $ficheRepository;
    }
    public function chercherFiche(string $referentiel, string $num_taxonomique): ?Fiche {
        $nom_page = $this->formaterPageNom($referentiel, $num_taxonomique);
        $fiche = $this->ficheRepository->findOneBy(['tag' => $nom_page, 'derniere_version' => 1]);

        if (!$fiche) {
            $nom_page = $this->formaterPageNomGlobal($referentiel, $num_taxonomique);
            $fiche = $this->ficheRepository->findOneBy(['tag' => $nom_page, 'derniere_version' => 1]);
        }

        return $fiche;
    }

    public function formaterPageNom($referentiel, $nt) {
        return 'SmartFlore'.strtoupper($referentiel).'nt'.$nt;
    }

    public function formaterPageNomGlobal($referentiel, $nt) {
        return strtoupper($referentiel).'nt'.$nt;
    }

    /**
     * Retourne le referentiel et le numero taxonomique à partir d'un indentifiant d'individu
     * Ex: mange 'SmartFloreBDTFXnt6200#1' et recrache array('BDTFX', '6200')
     * @param      string  $individu_id  (ex: SmartFloreBDTFXnt6200#1)
     * @return     array
     */
    public function digestIndividuId($individu_id): array {
        $infos = str_replace('smartflore', '', strtolower($individu_id));
        $infos = preg_replace('/#\d+$/i', '', $infos);

        return explode("nt", $infos);
    }

    public function splitNt($page) {
        $page = str_replace('SmartFlore', '', $page);
        return explode("nt", $page);
    }
}