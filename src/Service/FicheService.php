<?php

namespace App\Service;

use App\Entity\Fiche;
use App\Model\FicheCollection;
use App\Model\FicheResultats;
use App\Repository\FicheRepository;
use App\Service\EfloreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class FicheService extends AbstractController
{
    private EfloreService $efloreService;
    private FicheRepository $ficheRepository;
    private SerializerInterface $serializer;
    private SharedService $sharedService;

    public function __construct(
    EfloreService $efloreService,
        FicheRepository $ficheRepository,
        SerializerInterface $serializer,
        SharedService $sharedService
    ) {
        $this->efloreService = $efloreService;
        $this->ficheRepository = $ficheRepository;
        $this->serializer = $serializer;
        $this->sharedService = $sharedService;
    }

    public function getPagination(Request $request): array
    {
        return [
            'debut' => $request->query->get('debut', 0),
            'limite' => $request->query->get('limite', 20)
        ];
    }

    public function mapRequestFilters(Request $request): array
    {
        $retourAutorises = ['un', 'min', 'max'];
        //un= Recherche d'une page, pour édition directe à partir d'un lien TODO: encore utile ?
        //min= Recherche et renvoie uniquement les noms pour assurer une autocomplétion réactive
        //max= Recherche normale, avec renvoi d'infos complètes
        if (!in_array($request->query->get('retour'), $retourAutorises, true)) {
            $request->query->set('retour', 'max');
        }

        return [
            'debut' => $request->query->get('debut', 0),
            'limite' => $request->query->get('limite', 99),
            'referentiel' => $request->query->get('referentiel', '%'),
            'referentiel_verna' => $request->query->get('referentiel_verna', true),
            'nom_verna' => $request->query->get('nom_verna', false),
            'filtre' => $request->query->get('filtre', null),
            'num_tax' => $request->query->get('num_tax', '%'),
            'recherche' => $request->query->get('recherche', '%'),
            'pages_existantes' => $request->query->get('pages_existantes', false),
            'retour' => $request->query->get('retour')
        ];
    }

    public function mapRechercheParameters(Request $request): array
    {
        return [
            'debut' => $request->query->get('debut', 0),
            'limite' => $request->query->get('limite', 99),
            'referentiel' => $request->query->get('referentiel', '%'),
            'referentiel_verna' => $request->query->get('referentiel_verna', true),
            'nom_verna' => $request->query->get('nom_verna', false),
            'filtre' => $request->query->get('filtre', null),
            'num_tax' => $request->query->get('num_tax', '%'),
            'recherche' => $request->query->get('recherche', '%'),
        ];
    }

    public function referentielAndNtExist(array $filtres): bool
    {
        $referentiel = $filtres['referentiel'];
        $num_tax = $filtres['num_tax'];

        if ($referentiel == '%' || $num_tax == '%') {
            return false;
        }
         return true;
    }

    public function getPagesPourRechercheAsync($recherche) {
        $retour = array('pagination' => array('total' => 0), 'resultats' => array());

        if($recherche['nom_verna'] == "true") {
            $case_nom = 'nom';
            $infos = $this->efloreService->consulterRechercheNomsVernaEflore($recherche);
        } else {
            $case_nom = 'nom_sci';
            $infos = $this->efloreService->consulterRechercheNomsSciEflore($recherche);
        }

        if(!empty($infos['entete']) && $infos['entete']['total'] != 0) {
            $retour['pagination'] = $infos['entete']['total'];
            foreach ($infos['resultat'] as $nom) {
                $retour['resultats'][] = $nom[$case_nom];
            }
        }

        return $retour;
    }

    public function getPagesPourRechercheNormale($recherche): array {
        if ($recherche['retour'] === 'un' || $this->referentielAndNtExist($recherche)) {
            return $this->traiterRechercheTaxonUnique($recherche);
        }

        if ($recherche['nom_verna'] === "true") {
            return $this->traiterRechercheNomVerna($recherche);
        }

        return $this->traiterRechercheNomSci($recherche);
    }

    public function formaterResultatsFiches($infos, $list, $filtres): FicheCollection
    {
        $resultats = [];
        foreach ($infos['resultat'] as $taxon) {
            if (isset($taxon['num_taxonomique'])) {
                $resultats[] = $this->formaterTaxon($taxon, $filtres['referentiel']);
            }
        }
        $list->setResultats($resultats);
        return $list;
    }

    private function formaterTaxon(array $taxon, string $referentiel): FicheResultats {
        $resultat = new FicheResultats();
        $resultat->setNumTaxonomique($taxon['num_taxonomique']);
        $resultat->setNomSci($taxon['nom_sci']);
        $resultat->setNomSciComplet($taxon['nom_sci_complet']);
        $resultat->setRetenu($taxon['retenu']);
        $resultat->setNumNom($taxon['id']);
        $resultat->setReferentiel($referentiel);
        $resultat->setNomsVernaculaires($taxon['noms_vernaculaires'] ?? []);

        $fiche = $this->sharedService->chercherFiche($referentiel, $taxon['num_taxonomique']);
        if ($fiche) {
            $resultat->setFiche($fiche);
        }

        return $resultat;
    }

    private function traiterRechercheTaxonUnique(array $recherche): array {
        $infos = $this->efloreService->getInfosTaxons($recherche['referentiel'], $recherche['num_tax']);
        if (empty($infos)) return [];

        $keys = array_keys($infos['resultat']);
        $num_nom = array_pop($keys);
        $infos['resultat'][$num_nom]["id"] = $num_nom;
        $infos['resultat'][$num_nom]["nom_retenu.id"] = $num_nom;

        if (in_array($recherche['referentiel'], ['bdtfx', 'bdtfxa'])) {
            $infos['resultat'][$num_nom]['noms_vernaculaires'] = $this->getNomsVernaculaires($recherche['referentiel'], $recherche['num_tax']);
        }

        return $infos;
    }

    private function traiterRechercheNomVerna(array $recherche): array {
        $infos = $this->efloreService->consulterRechercheNomsVernaEflore($recherche);
        $fiches = [];

        if (empty($infos)) return [];

        foreach ($infos['resultat'] as $taxon) {
            $nums_taxo = explode(",", $taxon['num_taxon']);
            foreach ($nums_taxo as $num_taxo) {
                $infosTaxon = $this->efloreService->getInfosTaxons($recherche['referentiel'], $num_taxo);
                if (!isset($infosTaxon['resultat'])) continue;

                foreach ($infosTaxon['resultat'] as $num_nom => $value) {
                    if (!isset($fiches[$num_nom])) {
                        $fiches[$num_nom] = $value;
                        $fiches[$num_nom]['num_taxonomique'] = $num_taxo;
                        $fiches[$num_nom]['id'] = $num_nom;
                        $fiches[$num_nom]['nom_retenu.id'] = $num_nom;
                        $fiches[$num_nom]['noms_vernaculaires'] = [];
                    }
                    $fiches[$num_nom]['noms_vernaculaires'][] = $taxon['nom'];
                }
            }

        }

        return [
            "entete" => $infos['entete'],
            "resultat" => $fiches
        ];
    }

    private function traiterRechercheNomSci(array $recherche): array {
        $infos = $this->efloreService->consulterRechercheNomsSciEflore($recherche);
        if (empty($infos)) return [];

        foreach ($infos['resultat'] as &$taxon) {
            $taxon['noms_vernaculaires'] = [];

            if (in_array($recherche['referentiel'], ['bdtfx', 'bdtfxa'])) {
                $taxon['noms_vernaculaires'] = $this->getNomsVernaculaires($recherche['referentiel'], $taxon['num_taxonomique']);
            }
        }

        return $infos;
    }

    private function getNomsVernaculaires(string $referentiel, string $num_tax): array {
        $noms = [];
        $vernacular_names = $this->efloreService->getVernacularName($referentiel, $num_tax, true);
        if ($vernacular_names) {
            foreach ($vernacular_names as $vn) {
                $noms[] = $vn['nom'];
            }
        }
        return $noms;
    }

}