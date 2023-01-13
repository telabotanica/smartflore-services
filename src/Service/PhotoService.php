<?php

namespace App\Service;

use App\Model\Photo;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PhotoService
{
    private $client;

    public function __construct(
    )
    {
        /**
         * @var $client HttpClientInterface
         */
        $this->client = HttpClient::create();

    }

    public function process(File $file, Photo $photoInfos, array $user, string $filePath): void
    {
        // TODO: Implémenter les fonctions getPhotoId et addPhotoToTrail
        $this->savePhoto($file, $photoInfos, $user, $filePath);
//        $pictureId = $this->getPhotoId();
//        $this->addPhotoToTrail($pictureId);
    }

    // Fonction enregistrant la photo uploadé dans le cel en faisant appel au widget de saisie
    public function savePhoto(File $file, Photo $photoInfos, array $user, string $filePath):void
    {
        // TODO Débugger l'appel au widget de saisie
        $taxon = $photoInfos->getTaxon();
        $coordinates = $photoInfos->getPosition();
        $fileName = $file->getClientOriginalName();

        // Transform file to base64
        $type = pathinfo($filePath, PATHINFO_EXTENSION);
        $dataFile = file_get_contents($filePath);
        $base64 = 'data:image/b64;base64,' . base64_encode($dataFile);

        $data = [
            [
                'date' => $photoInfos->getDate(),
                'latitude' => $coordinates['lat'],
                'longitude' => $coordinates['lon'],
                'nom_sel' => $taxon['scientific_name'],
                'num_nom_sel' => $taxon['name_id'],
                'referentiel' => $taxon['taxon_repository'],
                'image_nom' => $fileName,
                'image_b64'=> $base64
            ]
        ];

        $formFields = [
            'obsId1'=>$data,
            'utilisateur'=>$user
//            'file' => DataPart::fromPath($filePath)
        ];
//        $formData = new FormDataPart($formFields);

//        $url = 'http://api-test.tela-botanica.org/service:cel:CelWidgetSaisie';
        $url = 'https://beta.tela-botanica.org/widget:cel:saisie';
        $response = $this->client->request('PUT', $url,[
//            'body' => $formData->bodyToIterable()
            'body' => $formFields
//            'query' => json_encode($data)
        ]);

        if (200 !== $response->getStatusCode() || 'OK' !== $response->getContent()) {
            throw new \Exception('Erreur durant l\'appel au widget de saisie');
        }

        //TODO: Supprimer la photo du dossieruploads_tmp une fois celle-ci enregistrer dans le cel
    }

    // Fonction récupérant l'id de la photo enregistré dans le cel
    public function getPhotoId():void
    {

    }

    // Ajout de la photo de l'utilisateur sur l'obs de son sentier
    public function addPhotoToTrail():void
    {

    }
}