<?php

namespace App\Controller;

use App\Model\Photo;
use App\Model\Taxon;
use App\Model\User;
use App\Service\AnnuaireService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     *                  description="Picture to upload",
     *                  type="file"
     *              ),
     *          ),
     *      ),
     *  )
     *  @OA\Parameter(
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
    public function uploadPhoto(Request $request,
                                SerializerInterface $serializer,
                                ValidatorInterface $validator,
                                AnnuaireService $annuaire)
    {
        // TODO Récupérer les infos du taxon correctement
        // TODO Ne pas oublier de dé-commenter les infos utilisateurs
        
        // TODO Envoyer $data dans CEL_WIDGET_SAISIE puis récupérer l'id de la photo (avec le last index ?)

        /*
         // Récupération des infos de l'utilisateur
        $token=null;
        $token = $request->headers->get('Authorization');
        ['token' => $token, 'error' => $error] = $annuaire->refreshToken($token);
        $tokenInfos = $annuaire->decodeToken($token);

        $data['nom']=$tokenInfos['nom'];
        $data['prenom']=$tokenInfos['prenom'];
        $data['courriel']=$tokenInfos['sub'];
        $data['id_utilisateur']=$tokenInfos['id'];
        */

        // Récupération des infos de l'observation
        $photo = $serializer->deserialize($request->get('observation'), Photo::class, 'json');
        $data['date']=$photo->getDate();
        $data['latitude']=$photo->getPosition()['lat'];
        $data['longitude']=$photo->getPosition()['lon'];

        // Récupération des infos du taxon
//        $taxon = $photo->getTaxo();

        // Récupération du nom du fichier uploadé
        if ($request->files->get('photo')) {
            $data['image_nom'] = $request->files->get('photo')->getClientOriginalName();
        }
        var_dump($data);

        return new JsonResponse('Photo uploadé', 201);
    }
}
