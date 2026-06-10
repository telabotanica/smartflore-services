<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class ExportControllerTest extends ControllerTestCase
{
    public function testExportCsvUnauthenticated(): void
    {
        $this->client->request('GET', '/export/csv');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testExportCsvForbidden(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('GET', '/export/csv', ['token' => 'fake-token-123']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testExportCsv(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('GET', '/export/csv', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('id,titre,auteurId', $response->getContent());
    }

    public function testExportCsvContainsExpectedHeaders(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('GET', '/export/csv', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $lines = explode("\n", trim($content));
        $headers = str_getcsv($lines[0]);
        $this->assertContains('id', $headers);
        $this->assertContains('titre', $headers);
        $this->assertContains('auteur', $headers);
        $this->assertContains('etat', $headers);
    }
}
