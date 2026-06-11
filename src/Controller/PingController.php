<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Ping;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PingController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator)
    {
    }
    /**
     * @OA\Response (
     *     response="201",
     *     description="Ping Created",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string", example="Ping saved in Database")
     *     )
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing ping informations",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Ping::class, groups={"create"})
     *     )
     * )
     * @OA\Tag(name="Ping")
     * @OA\Post(
     *     summary="Save trails access (public)",
     * )
     */
    #[Route(path: '/ping', name: 'Ping', methods: ['POST'])]
    public function ping(Request $request): Response
    {
        $ping = $this->serializer->deserialize($request->getContent(), Ping::class, 'json');
        $errors = $this->validator->validate($ping);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string)$errors);
        }

        if ($ping->isFromWebsite() === true) {
            $ip = $request->getClientIp();

            $existingPing = $this->entityManager
                ->getRepository(Ping::class)
                ->findTodayPingByIpAndTrail($ip, $ping->getTrail());

            if ($existingPing !== null) {
                return new JsonResponse('Ping already registered today', Response::HTTP_OK);
            }

            $ping->setIp($ip);
        }

        $this->entityManager->persist($ping);
        $this->entityManager->flush();

        return new JsonResponse('Ping saved in Database', Response::HTTP_CREATED);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail consultation",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Ping::class, groups={"show_ping"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example="146"
     * )
     * @OA\Tag(name="Ping")
     * @OA\Get(
     *     summary="Get a trail number of consultations",
     * )
     */
    #[Route(path: '/ping/{id}', name: 'show_ping', methods: ['GET'])]
    public function pingDetails(
        $id
    ): JsonResponse {
        $pings = $this->entityManager->getRepository(Ping::class)->findBy(['trail' => $id]);

        $json = $this->serializer->serialize($pings, 'json', ['groups' => 'show_ping']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
