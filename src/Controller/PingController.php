<?php

namespace App\Controller;

use App\Entity\Ping;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PingController extends AbstractController
{
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
     * @Route("/ping", name="Ping",methods={"POST"})
     */
    public function ping(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        $ping = $serializer->deserialize($request->getContent(), Ping::class, 'json');
        $errors = $validator->validate($ping);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string)$errors);
        }

        $entityManager->persist($ping);
        $entityManager->flush();

        return new JsonResponse('Ping saved in Database', 201);
    }
}
