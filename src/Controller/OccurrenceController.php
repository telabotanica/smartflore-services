<?php

namespace App\Controller;

use App\Entity\Occurrence;
use App\Entity\Sentier;
use App\Repository\ImageRepository;
use App\Repository\OccurrenceRepository;
use App\Repository\SentierRepository;
use App\Service\AnnuaireService;
use App\Service\CacheFileService;
use App\Service\CreateTrailService;
use App\Service\SharedService;
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
    private SentierRepository $sentierRepository;
    private SharedService $sharedService;
    private CacheFileService $cacheFile;

    public function __construct(
        SerializerInterface $serializer,
        AnnuaireService $annuaire,
        CreateTrailService $createTrail,
        EntityManagerInterface $em,
        OccurrenceRepository $occurrenceRepository,
        ImageRepository $imageRepository,
        SentierRepository $sentierRepository,
        SharedService $sharedService,
        CacheFileService $cacheFile
    ) {
        $this->serializer = $serializer;
        $this->annuaire = $annuaire;
        $this->createTrail = $createTrail;
        $this->em = $em;
        $this->occurrenceRepository = $occurrenceRepository;
        $this->imageRepository = $imageRepository;
        $this->sentierRepository = $sentierRepository;
        $this->sharedService = $sharedService;
        $this->cacheFile = $cacheFile;
    }

    /**
     * @OA\Response(
     *     response="201",
     *     description="created",
     *      @Model(type=Sentier::class, groups={"show_trail"})
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing occurrence information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Occurrence::class, groups={"create_trail"})
     *     )
     * )
     * @OA\Parameter(
     *     name="sentier_id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Occurrences")
     * @OA\Post(
     *     summary="Add an occurrence to a trail"
     * )
     * @Route("/occurrence/{sentier_id}", name="post_occurrence", methods={"POST"})
     */
    public function addOccurrence(Request $request, int $sentier_id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de l\'ajout de l\'occurrence: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $content = json_decode($request->getContent());
        $trail = $this->sentierRepository->findOneBy(['id' => $sentier_id]);

        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $sentier_id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $sentier_id .')'], Response::HTTP_FORBIDDEN);
        }

        // On empêche les modification d'un sentier une fois celui-ci publié
        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $sentier_id .')'], Response::HTTP_FORBIDDEN);
        }

        if (!$request->getContent()) {
            return new JsonResponse(['error' => 'No data available in order to add occurrence to trail (id: '. $sentier_id .')'], Response::HTTP_BAD_REQUEST);
        }

        $occurrence = $this->serializer->deserialize($request->getContent(), Occurrence::class, 'json', [
            'groups' => ['add_occurrence']
        ]);

        $occurrence = $this->createTrail->setTaxonToOccurrence($occurrence, $content);
        if (isset($content->image_id)) {
            $occurrence = $this->createTrail->setImagesToOccurrence($occurrence, $content->image_id);
        }
        if (isset($content->images)) {
            foreach ($content->images as $image) {
                $this->createTrail->setImagesToOccurrence($occurrence, $image->id);
            }
        }
        $this->createTrail->getCardTag($occurrence);
        $occurrence->setUserId(($trail->getAuthorId()));

        $trail->addOccurrence($occurrence);
        $trail->setDateModification(new \DateTime());
        $this->createTrail->addNbTaxonsToTrail($trail);

        if (!$trail->getDetails()){
            $trail = $this->sharedService->addDetailToTrail($trail);
        }

        $this->em->persist($trail);
        $this->em->flush();

        // --- Mise à jour du cache trail après ajout d'occurrence ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_CREATED, [], true);
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
     * @OA\Put(
     *     summary="update an occurrence localisation, anecdote, or add an image"
     * )
     * @Route("/occurrence/{id}", name="update_occurrence", methods={"PUT"})
     */
    public function updateOccurrence(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour de l\'occurrence: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $occurrence = $this->occurrenceRepository->findOneBy(['id' => $id]);
        $content = json_decode($request->getContent());

        if (!$occurrence) {
            return new JsonResponse(['error' => 'Occurrence not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateOccurrence($user, $occurrence)) {
            return new JsonResponse(['error' => 'You are not allowed to update this occurrence (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

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

        $trail = $this->sentierRepository->findOneBy(['id' => $occurrence->getSentier()->getId()]);
        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        if (!$trail->getDetails()){
            $trail = $this->sharedService->addDetailToTrail($trail);
        }

        $trail->setDateModification(new \DateTime());
        $this->em->persist($trail);

        $this->em->persist($occurrence);
        $this->em->flush();

        // --- Mise à jour du cache trail après modification de l'occurrence ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        return new JsonResponse($this->serializer->serialize($occurrence, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="deleted",
     *      @OA\JsonContent(
     *          type="string",
     *          example="Occurrence deleted (id: 146)"
     *      )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The occurrence ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Occurrences")
     * @OA\Delete(
     *     summary="Remove an occurrence from a trail"
     * )
     * @Route("/occurrence/{id}", name="delete_occurrence", methods={"DELETE"})
     */
    public function deleteOccurrence(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la suppression de l\'occurrence: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $occurrence = $this->occurrenceRepository->findOneBy(['id' => $id]);
        if (!$occurrence) {
            return new JsonResponse(['error' => 'Occurrence not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        $trail = $occurrence->getSentier();

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        // On empêche les modification d'un sentier une fois celui-ci publié
        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $occurrence->setDateSuppression(new \DateTime());
        $trail->setDateModification(new \DateTime());
        $trail->removeOccurrence($occurrence);
        $this->createTrail->addNbTaxonsToTrail($trail);

        $this->em->persist($trail);
        $this->em->flush();

        // --- Mise à jour du cache trail après suppression d'occurrence ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        return new JsonResponse( 'Occurrence deleted (id: '. $id .')', Response::HTTP_OK);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="deleted",
     *      @Model(type=Occurrence::class, groups={"show_trail"})
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The image ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Occurrences")
     * @OA\Delete(
     *     summary="Remove an image from occurreence"
     * )
     * @Route("/occurrence/image/{id}", name="delete_image", methods={"DELETE"})
     */
    public function deleteImage(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la suppression de l\'occurrence: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $image = $this->imageRepository->findOneBy(['id' => $id]);
        if (!$image) {
            return new JsonResponse(['error' => 'Image not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        $occurrence = $image->getOccurrence();
        if (!$this->annuaire->canUpdateOccurrence($user, $occurrence)) {
            return new JsonResponse(['error' => 'You are not allowed to update this occurrence (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $trail = $occurrence->getSentier();

        $this->em->remove($image);
        $this->em->flush();

        // --- Mise à jour du cache trail après suppression d'image ---
        if ($trail) {
            $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);
        }

        return new JsonResponse($this->serializer->serialize($occurrence, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }
}