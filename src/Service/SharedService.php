<?php

namespace App\Service;

use App\Entity\Fiche;
use App\Entity\Sentier;
use App\Repository\FicheRepository;

class SharedService
{
    private string $smartfloreFrontUrl;
    private FicheRepository $ficheRepository;

    public function __construct(string $smartfloreFrontUrl, FicheRepository $ficheRepository)
    {
        $this->smartfloreFrontUrl = $smartfloreFrontUrl;
        $this->ficheRepository = $ficheRepository;
    }
    public function chercherFiche(string $referentiel, int $num_taxonomique): ?Fiche {
        $nom_page = $this->formaterPageNom($referentiel, (string) $num_taxonomique); // Fiche SmartFlore eg. SmartFloreBDTFXnt6200
        $fiche = $this->ficheRepository->findOneBy(['tag' => $nom_page, 'derniere_version' => 1]);

        if (!$fiche) {
            $nom_page = $this->formaterPageNomGlobal($referentiel, (string) $num_taxonomique); // Fiche globale eg. BDTFXnt36750
            $fiche = $this->ficheRepository->findOneBy(['tag' => $nom_page, 'derniere_version' => 1]);
        }

        return $fiche;
    }

    public function formaterPageNom($referentiel, string $nt): string {
        return 'SmartFlore'.strtoupper($referentiel).'nt'.$nt;
    }

    public function formaterPageNomGlobal($referentiel, string $nt): string {
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

    public function splitNt($page): array {
        $page = str_replace('SmartFlore', '', $page);
        return explode("nt", $page);
    }

    public function addDetailToTrail(Sentier $trail): Sentier
    {
        $trail->setDetails($this->getSentierFrontUrl($trail));

        return $trail;
    }

    public function getSentierFrontUrl(Sentier $trail): string
    {
        return $this->smartfloreFrontUrl.'trail/'.$trail->getId();
    }
}