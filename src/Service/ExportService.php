<?php

namespace App\Service;

use DateTime;
use Symfony\Component\HttpFoundation\Response;

class ExportService
{
    public function createCsv(array $list, string $fileName): Response
    {
        $fp = fopen('php://temp', 'w');
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $fileName . '.csv');

        return $response;
    }

    public function exportTrailsCsv(array $sentiers): Response {
        $list = [[
            'id',
            'titre',
            'auteurId',
            'auteur',
            'email',
            'dateCreation',
            'dateDerniereModif',
            'etat',
            'pmr',
            'meilleuresSaisons',
            "details",
            "nbTaxons",
            "nbOccurences",
        ]];

        foreach ($sentiers as $sentier) {
            $dateCreation = $sentier->getDateCreation()->format('Y/m/d H:i:s');
            $dateDerniereModif = $sentier->getDateModification() ? $sentier->getDateModification()->format('Y/m/d H:i:s') : null;

            $list[] = [
                $sentier->getId(),
                $sentier->getNom(),
                $sentier->getAuthorId(),
                $sentier->getAuteur(),
                $sentier->getAuteurEmail(),
                $dateCreation,
                $dateDerniereModif,
                $sentier->getStatus(),
                $sentier->getPmr(),
                is_array($sentier->getMeilleuresSaisons()) ? implode(',', $sentier->getMeilleuresSaisons()) : $sentier->getMeilleuresSaisons(),
                $sentier->getDetails(),
                $sentier->getNbTaxons(),
                $sentier->getOccurrencesCount()
            ];
        }

        $today = (new DateTime())->format('YmdHis');

        return $this->createCsv($list, 'export_sentiers_'. $today);
    }
}