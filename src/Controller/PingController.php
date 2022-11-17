<?php

namespace App\Controller;

use App\Entity\Ping;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PingController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="Ping saved in database",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string")
     *     )
     * )
     * @OA\Parameter(
     *     name="isLogged",
     *     in="query",
     *     description="Is the user logged in ?",
     *     example="true",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="isLocated",
     *     in="query",
     *     description="Is the user located ?",
     *     example="false",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="isCloseToTrail",
     *     in="query",
     *     description="Is the user close to a registered trail ?",
     *     example="false",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="isOnline",
     *     in="query",
     *     description="Is the user online ?",
     *     example="true",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="date",
     *     in="query",
     *     description="Date & Time of the ping",
     *     example="2022-11-16 09:54:22 ",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="trail",
     *     in="query",
     *     description="Trail id",
     *     example="25",
     * @OA\Schema(type="integer")
     * )
     *
     * @OA\Tag(name="Ping")
     * @Route("/ping", name="Ping",methods={"POST"})
     */
    public function ping(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ping = new Ping();
        $error = false;

        $isLogged = $request->query->get('isLogged');
        $isLocated = $request->query->get('isLocated');
        $isOnline = $request->query->get('isOnline');
        $isCloseToTrail = $request->query->get('isCloseToTrail');
        $trail = $request->query->get('trail');
        $date = $request->query->get('date');

        $requiredValues = [$isLogged, $isLocated, $isOnline, $isCloseToTrail, $trail];

        // If one value is missing in the request, there is no need to go further
        if (in_array(null, $requiredValues)) {
            error_log('Missing value');
            return new JsonResponse('Error, Ping not saved in Database', 200);
        } else {
            $form = $this->createFormBuilder()
                ->add('isLogged', CheckboxType::class, [
                    'required' => true,
                ])
                ->add('isLocated', CheckboxType::class, [
                    'required' => true,
                ])
                ->add('isOnline', CheckboxType::class, [
                    'required' => true,
                ])
                ->add('isCloseToTrail', CheckboxType::class, [
                    'required' => true,
                ])
                ->add('trail', IntegerType::class, [
                    'required' => true,
                ])
                ->add('date', TextType::class, [
                    'required' => false,
                ])
                ->getForm();

            foreach ($request->query as $key => $value) {
                // Filter to transform request values (string) into boolean or integer
                if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } elseif (filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) !== null) {
                    $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                }

                // Try to set the transformed value in the form, if the value got the wrong format, an error is thrown
                try {
                    $form->get($key)->setData($value);
                } catch (\Exception $e) {
                    $error = true;
                    error_log($e->getMessage());
                }
            }

            if (!$error) {
                $ping->setIsLogged($form->get('isLogged')->getData())
                    ->setIsLocated($form->get('isLocated')->getData())
                    ->setIsOnline($form->get('isOnline')->getData())
                    ->setIsCloseToTrail($form->get('isCloseToTrail')->getData())
                    ->setTrail($form->get('trail')->getData());

                if ($date) {
                    $ping->setDate($form->get('date')->getData());
                }

                $entityManager->persist($ping);
                $entityManager->flush();

                return new JsonResponse('Ping saved in Database', 200);
            } else {
                return new JsonResponse('Error, Ping not saved in Database', 200);
            }
        }
    }
}
