<?php

namespace App\Tests\Controller;

use App\Model\User;
use App\DataFixtures\AppFixtures;
use App\Service\AnnuaireService;
use App\Service\CreateTrailService;
use App\Tests\Mock\EfloreApiMock;
use App\Tests\Mock\FakeHttpClient;
use App\Tests\Mock\TrailsApiMock;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class ControllerTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->loadFixtures();
    }

    protected function loadFixtures(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['ping', 'favoris', 'fiche', 'image', 'occurrence', 'path', 'sentier'] as $table) {
            $conn->executeStatement("TRUNCATE TABLE $table");
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->clearCacheFiles();

        $loader = new Loader();
        $loader->addFixture(new AppFixtures());

        $executor = new ORMExecutor($this->entityManager, new ORMPurger($this->entityManager));
        $executor->execute($loader->getFixtures(), false);
    }

    private function clearCacheFiles(): void
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $cachePath = $projectDir . '/bundles/var/cache_smartflore';
        $fs = new Filesystem();
        if ($fs->exists($cachePath)) {
            $fs->remove($cachePath);
        }
    }

    protected function mockAnnuaireService(?User $user = null, ?string $token = 'fake-token-123', bool $isAdmin = false): AnnuaireService
    {
        if ($user === null) {
            $user = $this->createTestUser();
        }

        $annuaire = $this->createMock(AnnuaireService::class);

        $annuaire->method('getRequestToken')
            ->willReturn($token);

        $annuaire->method('getUserInfos')
            ->willReturn($user);

        $annuaire->method('getUser')
            ->willReturn($user);

        $annuaire->method('getUserFromRequest')
            ->willReturn(['user' => $user, 'token' => $token, 'error' => null]);

        $annuaire->method('canUpdateTrail')
            ->willReturnCallback(function ($u, $trail) {
                return $u->getId() === $trail->getAuthorId();
            });

        $annuaire->method('canUpdateOccurrence')
            ->willReturnCallback(function ($u, $occurrence) {
                return $u->getId() === $occurrence->getUserId();
            });

        $annuaire->method('isAdmin')
            ->willReturn($isAdmin);

        $annuaire->method('listAdmin')
            ->willReturn($isAdmin ? [$user?->getEmail() ?? AppFixtures::ADMIN_EMAIL] : []);

        $annuaire->method('getCookieName')
            ->willReturn('tb_auth');

        self::getContainer()->set(AnnuaireService::class, $annuaire);

        return $annuaire;
    }

    protected function mockAdminAnnuaireService(?User $user = null, ?string $token = 'fake-token-123'): AnnuaireService
    {
        return $this->mockAnnuaireService($user, $token, true);
    }

    protected function createTestUser(): User
    {
        $user = new User();
        $user->setId(AppFixtures::TEST_USER_ID);
        $user->setEmail(AppFixtures::TEST_USER_EMAIL);
        $user->setName('Test User');

        return $user;
    }

    protected function createAdminUser(): User
    {
        $user = new User();
        $user->setId(AppFixtures::TEST_USER_ID);
        $user->setEmail(AppFixtures::ADMIN_EMAIL);
        $user->setName('Admin User');

        return $user;
    }

    protected function mockHttpClient(): void
    {
        self::getContainer()->set(HttpClientInterface::class,
            new FakeHttpClient(TrailsApiMock::getResponses() + EfloreApiMock::getResponses()));
    }

    protected function mockCreateTrailService(bool $eligible = true): CreateTrailService
    {
        $createTrail = $this->createMock(CreateTrailService::class);
        $createTrail->method('isTrailEligible')->willReturn($eligible ? [] : ['nb_occurrences' => 'error']);

        self::getContainer()->set(CreateTrailService::class, $createTrail);

        return $createTrail;
    }

    protected function mockEfloreService(): void
    {
        $eflore = $this->createMock(\App\Service\EfloreService::class);
        $eflore->method('getTaxonRawInfo')
            ->willReturn(['nom_sci' => 'Acer campestre', 'nom_complet' => 'Acer campestre L.', 'id' => '141', 'genre' => 'Acer', 'famille' => 'Sapindaceae', 'num_taxonomique' => '141']);
        $eflore->method('getTaxon')
            ->willReturn(new \App\Model\Taxon());
        $eflore->method('getInfosTaxons')
            ->willReturn(['taxon' => ['id' => 141, 'scientific_name' => 'Acer campestre', 'taxon_repository' => 'bdtfx', 'name_id' => 8522]]);
        $eflore->method('getCardSpeciesImages')
            ->willReturn([]);
        $eflore->method('getVernacularName')
            ->willReturn([]);
        $eflore->method('getCardText')
            ->willReturn([]);

        self::getContainer()->set(\App\Service\EfloreService::class, $eflore);
    }

    protected function createAuthenticatedClient(string $token = 'fake-token-123'): KernelBrowser
    {
        $this->client = self::createClient([], [
            'HTTP_Authorization' => $token,
        ]);

        return $this->client;
    }
}
