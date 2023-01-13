<?php

namespace App\Controller;

use App\Model\Photo;
use App\Model\Taxon;
use App\Model\User;
use App\Service\AnnuaireService;
use App\Service\PhotoService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PhotoController extends AbstractController
{
    /**
     * @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="photo",
     *                  description="Picture to upload (format JPG)",
     *                  type="file"
     *              ),
     *          ),
     *      )
     * )
     *  @OA\Parameter(
     *     required=true,
     *     description="Taxon observé",
     *     name="observation",
     *     in="query",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Photo::class, groups={"create_photo"})
     *     )
     * )
     *
     * @OA\Response(response=201, description="Picture uploaded")
     *
     * @OA\Tag(name="Photo")
     * @Route("/photo", name="create_photo", methods={"POST"})
     */
    public function uploadPhoto(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        AnnuaireService $annuaire,
        PhotoService $addPhoto
    )
    {
        // TODO Ne pas oublier de dé-commenter les infos utilisateurs

        // TODO Envoyer $data dans CEL_WIDGET_SAISIE puis récupérer l'id de la photo (avec le last index ?)

        if (empty($request->files->get('photo'))) throw new \Exception('No file uploaded');

        $file = $request->files->get('photo');
        $fileName = $file->getClientOriginalName();
        $fileNameParts = explode('.', $fileName);
        $fileExtension = end($fileNameParts);

        // Le widget de saisie n'accepte que les images .jpg
        if ($fileExtension != 'jpg') throw new \Exception('Only .jpg files are allowed');

        // On enregistre l'image en local
        $uploadPath = $this->getParameter('uploads_directory');
        try {
            $file->move($uploadPath,$fileName);
        } catch (FileException $e){
            throw new \Exception('erreur durant le téléchargement du fichier');
        }

        $filePath=$uploadPath.'/'.$fileName;


        $photoInfos = $serializer->deserialize($request->get('observation'), Photo::class, 'json');
        $errors = $validator->validate($photoInfos);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return new JsonResponse(['error' => $errorsString]);
        }

        $token=null;
        $token = $request->headers->get('Authorization');
        $user = $annuaire->getUserInfos($token);

         // Récupération des infos de l'utilisateur
        /*
        $token=null;
        $token = $request->headers->get('Authorization');
        ['token' => $token, 'error' => $error] = $annuaire->refreshToken($token);
        $tokenInfos = $annuaire->decodeToken($token);

        $data['nom']=$tokenInfos['nom'];
        $data['prenom']=$tokenInfos['prenom'];
        $data['courriel']=$tokenInfos['sub'];
        $data['id_utilisateur']=$tokenInfos['id'];
        */

        $addPhoto->process($file, $photoInfos, $user, $filePath);

        return new JsonResponse('photo uploaded', 201);
    }
}
