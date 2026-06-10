<?php

namespace App\Controller;

use App\Repository\SentierRepository;
use App\Service\AnnuaireService;
use App\Service\ExportService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    public function __construct(private readonly AnnuaireService $annuaire, private readonly ExportService $exportService, private readonly SentierRepository $sentierRepository)
    {
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Liste des sentiers généré en csv"
     * )
     * @OA\Tag(name="Export")
     * @OA\Get(
     *     summary="Generate a csv file with all trails (admins only)",
     * )
     */
    #[Route(path: '/export/csv', name: 'export_csv', methods: ['GET'])]
    public function export_csv(Request $request): Response
    {
        ['user' => $user, 'token'=> $token, 'error' => $error] = $this->annuaire->getUserFromRequest($request);
        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }
        if (!$user || !$token) {
            return new JsonResponse(['error' => 'Erreur d\'authentification, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to display this list of trails'], Response::HTTP_FORBIDDEN);
        }

        $list = $this->sentierRepository->findBy(['date_suppression' => null]);

        $response = $this->exportService->exportTrailsCsv($list);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');

        return $response;
    }
}
