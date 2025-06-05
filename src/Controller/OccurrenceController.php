<?php

namespace App\Controller;

use App\Entity\Occurrence;
use App\Repository\ImageRepository;
use App\Repository\OccurrenceRepository;
use App\Service\AnnuaireService;
use App\Service\CreateTrailService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OccurrenceController extends AbstractController
{
    private SerializerInterface $serializer;
    private AnnuaireService $annuaire;
    private CreateTrailService $createTrail;
    private EntityManagerInterface $em;
    private OccurrenceRepository $occurrenceRepository;
    private ImageRepository $imageRepository;

    public function __construct(SerializerInterface $serializer, AnnuaireService $annuaire, CreateTrailService $createTrail, EntityManagerInterface $em, OccurrenceRepository $occurrenceRepository, ImageRepository $imageRepository)
    {
        $this->serializer = $serializer;
        $this->annuaire = $annuaire;
        $this->createTrail = $createTrail;
        $this->em = $em;
        $this->occurrenceRepository = $occurrenceRepository;
        $this->imageRepository = $imageRepository;
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="updated",
     *      @Model(type=Occurrence::class, groups={"show_trail"})
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing occurrence information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Occurrence::class, groups={"update_occurrence"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The occurrence ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Occurrences")
     * @Route("/occurrence/{id}", name="update_occurrence", methods={"PUT"})
     */
    public function updateOccurrence(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour du sentier: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $occurrence = $this->occurrenceRepository->findOneBy(['id' => $id]);
        if (!$occurrence) {
            return new JsonResponse(['error' => 'Occurrence not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateOccurrence($user, $occurrence)) {
            return new JsonResponse(['error' => 'You are not allowed to update this occurrence (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $content = json_decode($request->getContent());
        if (!$request->getContent()) {
            return new JsonResponse(['error' => 'No update requested on occurrence (id: '. $id .')'], Response::HTTP_BAD_REQUEST);
        }

        $occurrence = $this->serializer->deserialize($request->getContent(), Occurrence::class, 'json', [
            'groups' => ['occurrence_without_image'],
            'object_to_populate' => $occurrence,
        ]);

        // cas "image_id"
        if (isset($content->image_id)) {
            $existingImage = $this->imageRepository->findOneBy(['cel_image_id' => $content->image_id, "occurrence" => $occurrence]);
            if (!$existingImage) {
                $this->createTrail->setImagesToOccurrence($occurrence, $content->image_id);
            }
        }

        // cas  "images": [
        //        {
        //          "id": 3230199
        //        }
        //      ]
        if (isset($content->images)) {
            foreach ($content->images as $image) {
                $existingImage = $this->imageRepository->findOneBy(['cel_image_id' => $image->id, "occurrence" => $occurrence]);
                if (!$existingImage) {
                    $this->createTrail->setImagesToOccurrence($occurrence, $image->id);
                }
            }
        }

        $this->em->persist($occurrence);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($occurrence, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }
}
