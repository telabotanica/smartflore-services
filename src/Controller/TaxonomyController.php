<?php

namespace App\Controller;

use App\Model\Entete;
use App\Model\FicheCollection;
use App\Model\Referentiel;
use App\Model\Taxon;
use App\Service\EfloreService;
use App\Service\FicheService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class TaxonomyController extends AbstractController
{
    private SerializerInterface $serializer;
    private FicheService $ficheService;

    public function __construct(SerializerInterface $serializer, FicheService $ficheService)
    {
        $this->serializer = $serializer;
        $this->ficheService = $ficheService;
    }
    /**
     * @OA\Response (
     *     response="200",
     *     description="Taxonomic info and more",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Taxon::class, groups={"show_taxon", "full_images"})
     *     )
     * )
     * @OA\Parameter(
     *     name="taxonRepository",
     *     in="path",
     *     description="The taxon repository code (""référentiel"")",
     *     example="bdtfx",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="taxonNameId",
     *     in="path",
     *     description="The taxonomic name id (""num nom"")",
     *     example="141",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Taxon")
     * @OA\Get(
     *     summary="Get taxon infos (public)",
     * )
     * @Route("/taxon/{taxonRepository}/{taxonNameId}", name="show_taxon", methods={"GET"})
     */
    public function taxonInfo(
        SerializerInterface $serializer,
        EfloreService $eflore,
        string $taxonRepository,
        int $taxonNameId
    ) {
        $json = $serializer->serialize(
            $eflore->getTaxon($taxonRepository, $taxonNameId, true),
            'json', ['groups' => ['show_taxon', 'full_images']]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @OA\Response (
     *     response="200",
     *     description="get the taxon repository codes (referentiels)",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Referentiel::class, groups={"show_taxon", "list_referentiel"}))
     *     )
     * )
     * @OA\Tag(name="Taxon")
     * @OA\Get(
     *     summary="Get referentiels available (public)",
     * )
     * @Route("/taxon/referentiels", name="list_referentiel", methods={"GET"})
     */
    public function referentielInfo(SerializerInterface $serializer,EfloreService $eflore){
        $referentiels= $eflore->getTaxonRepositories();

        $json = $serializer->serialize($referentiels, 'json', ['groups' => 'list_referentiel']);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Fiches list",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=FicheCollection::class, groups={"list_fiche"})
     *     ),
     * )
     * @OA\Parameter(name="referentiel", in="query", required=true, description="Référentiel utilisé", @OA\Schema(type="string", example="bdtfx")),
     * @OA\Parameter(name="debut", in="query", required=false, description="Début de la recherche", @OA\Schema(type="integer", example=0)),
     * @OA\Parameter(name="limite", in="query", required=false, description="Nombre de résultats max", @OA\Schema(type="integer", example=10)),
     * @OA\Parameter(name="num_tax", in="query", required=false, description="Numéro taxonomique", @OA\Schema(type="integer", example=8522)),
     * @OA\Parameter(name="recherche", in="query", required=false, description="Terme recherché", @OA\Schema(type="string", example="acer")),
     * @OA\Parameter(name="retour", in="query", required=false, description="Mode de retour (un, min ou max)", @OA\Schema(type="string", example="max")),
     * @OA\Parameter(name="nom_verna", in="query", required=false, description="Recherche par noms vernaculaires", @OA\Schema(type="boolean", example=false)),
     * @OA\Parameter(name="referentiel_verna", in="query", required=false, description="Référentiel des noms vernaculaires", @OA\Schema(type="string", example="nvjfl")),
     * @OA\Parameter(name="filtre", in="query", required=false, description="Filtre de recherche", @OA\Schema(type="string", example="acer")),
     * @OA\Parameter(name="pages_existantes", in="query", required=false, description="Pages existantes uniquement", @OA\Schema(type="boolean", example=false)),
     * @OA\Tag(name="Taxon")
     * @OA\Get(
     *     summary="Search taxons with corresponding pages (public)",
     * )
     * @Route("/taxons", name="list_fiche", methods={"GET"})
     */
    public function getFiches(Request $request): Response
    {
        $filtres = $this->ficheService->mapRequestFilters($request);
        $list = new FicheCollection();

        // Recherche et renvoie uniquement les noms pour assurer une autocomplétion réactive (voir route /fiche/search)
        if ($request->query->get('retour') == 'min') {
            $list = $this->ficheService->getPagesPourRechercheAsync($filtres);
            return new JsonResponse($list, Response::HTTP_OK);
        }

        if (!isset($filtres['referentiel']) || $filtres['referentiel'] == '%') {
            return new JsonResponse(['error' => 'Le paramètre referentiel est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        // On recherche d'abord la liste de taxons correspondants aux critères de recherche
        $infos = $this->ficheService->getPagesPourRechercheNormale($filtres);

        if(!empty($infos['entete']) && $infos['entete']['total'] > 0) {
            $entete = $this->serializer->denormalize($infos['entete'], Entete::class, 'json');
            $list->setEntete($entete);

            // On ajoute les fiches
            $list = $this->ficheService->formaterResultatsFiches($infos, $list, $filtres);
        } else {
            $list->setEntete(new Entete());
            $list->setResultats([]);
        }

        $json = $this->serializer->serialize($list, 'json', ['groups' => ['list_fiche']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Parameter(name="referentiel", in="query", required=false, description="Référentiel utilisé", @OA\Schema(type="string", example="bdtfx")),
     * @OA\Parameter(name="debut", in="query", required=false, description="Début de la recherche", @OA\Schema(type="integer", example=0)),
     * @OA\Parameter(name="limite", in="query", required=false, description="Nombre de résultats max", @OA\Schema(type="integer", example=10)),
     * @OA\Parameter(name="num_tax", in="query", required=false, description="Numéro taxon", @OA\Schema(type="integer", example=141)),
     * @OA\Parameter(name="recherche", in="query", required=false, description="Terme recherché", @OA\Schema(type="string", example="acer")),
     * @OA\Parameter(name="retour", in="query", required=false, description="Mode de retour (ex: min)", @OA\Schema(type="string", example="min")),
     * @OA\Parameter(name="nom_verna", in="query", required=false, description="Inclure les noms vernaculaires", @OA\Schema(type="boolean", example=false)),
     * @OA\Parameter(name="referentiel_verna", in="query", required=false, description="Référentiel des noms vernaculaires", @OA\Schema(type="string", example="nvjfl")),
     * @OA\Parameter(name="filtre", in="query", required=false, description="Filtre de recherche", @OA\Schema(type="string", example="acer")),
     * @OA\Parameter(name="pages_existantes", in="query", required=false, description="Pages existantes uniquement", @OA\Schema(type="boolean", example=false)),
     * @OA\Response(
     *     response="200",
     *     description="Recherche de taxon",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="pagination", type="integer", example=47),
     *         @OA\Property(
     *          property="resultats",
     *          type="array",
     *          @OA\Items(type="string", example="Érable de Hers")
     *          )
     *     ),
     * )
     * //TODO: ajouter les pramètres de recherche
     * @OA\Tag(name="Taxon")
     * @OA\Get(
     *     summary="Search and return taxon names for reactive autocomplete search (public)",
     * )
     * @Route("/taxons/search", name="search_fiche", methods={"GET"})
     */
    //eg. http://127.0.0.1:8000/fiche?referentiel=bdtfx&debut=10&num_tax=141&recherche=acer&retour=min&nom_verna=false&limite=10&referentiel_verna=nvjfl&filtre=acer&pages_existantes=true
    public function RechercheTaxon(Request $request): Response
    {
        $filtres = $this->ficheService->mapRequestFilters($request);

        if ($request->query->get('retour') == 'min') {
            $retour = $this->ficheService->getPagesPourRechercheAsync($filtres);
            return new JsonResponse($retour, Response::HTTP_OK);
        }

        return new JsonResponse(['error' => 'Erreur, cette route doit être utilisé avec le paramètre retour=min'], Response::HTTP_BAD_REQUEST);
    }
}
