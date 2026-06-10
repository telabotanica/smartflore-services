<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Path;
use App\Entity\Ping;
use App\Entity\Fiche;
use App\Entity\Image;
use App\Entity\Sentier;
use App\Entity\Favoris;
use App\Entity\Occurrence;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    // IDs are per-table auto-increment (each entity type has its own sequence)
    public const TRAIL_VALIDATED_ID = 1;
    public const TRAIL_PENDING_ID = 2;
    public const TRAIL_DRAFT_ID = 3;
    public const TRAIL_DELETED_ID = 4;
    public const TRAIL_FULL_ID = 5;

    public const OCC_VALIDATED_1_ID = 1;
    public const OCC_VALIDATED_2_ID = 2;
    public const OCC_PENDING_1_ID = 3;
    public const OCC_FULL_1_ID = 4;
    public const OCC_FULL_2_ID = 5;

    public const IMAGE_FULL_1_ID = 1;
    public const IMAGE_FULL_2_ID = 2;

    public const PATH_FULL_ID = 1;

    public const FICHE_LATEST_ID = 1;
    public const FICHE_OLD_ID = 2;

    public const FAVORIS_1_ID = 1;

    public const PING_1_ID = 1;

    public const TEST_USER_ID = 'test_user_id_123';
    public const TEST_USER_EMAIL = 'test@tela-botanica.org';
    public const ADMIN_EMAIL = 'admin@tela-botanica.org';
    public const FICHE_BDTFX_NT = 3363;

    public function load(ObjectManager $manager): void
    {
        $this->createSentierValide($manager);
        $this->createSentierEnAttente($manager);
        $this->createSentierBrouillon($manager);
        $this->createSentierSupprime($manager);
        $this->createSentierComplet($manager);
        $this->createFiches($manager);
        $this->createFavoris($manager);
        $this->createPing($manager);

        $manager->flush();
    }

    private function createSentierValide(ObjectManager $manager): void
    {
        $t = new Sentier();
        $t->setNom('Forêt Enchantée');
        $t->setAuthorId(self::TEST_USER_ID);
        $t->setAuteur('Test User');
        $t->setAuteurEmail(self::TEST_USER_EMAIL);
        $t->setStatus('Validé');
        $t->setPosition(['start' => ['lat' => 43.61, 'lng' => 3.88], 'end' => ['lat' => 43.61, 'lng' => 3.88]]);
        $t->setPmr(1);
        $t->setMeilleuresSaisons([true, true, false, false]);
        $t->setPathLength(500);
        $t->setOccurrencesCount(2);
        $t->setNbTaxons(2);
        $t->setDetails('https://front.test/trail/foret-enchantee');
        $t->setDateCreation(new DateTime('2024-01-15'));
        $t->setDateModification(new DateTime('2024-06-01'));
        $t->setDatePublication(new DateTime('2024-02-01'));
        $manager->persist($t);

        $o1 = new Occurrence();
        $o1->setPosition(['lat' => 43.610, 'lng' => 3.876]);
        $o1->setAnecdotes('Un chêne magnifique');
        $o1->setTaxon([
            'taxon_repository' => 'bdtfx', 'name_id' => 54438,
            'taxonomic_id' => 54438, 'espece' => 'Quercus pubescens',
            'genre' => 'Quercus', 'famille' => 'Fagaceae',
        ]);
        $o1->setCardTag('SmartFloreBDTFXnt54438');
        $o1->setUserId(self::TEST_USER_ID);
        $t->addOccurrence($o1);
        $manager->persist($o1);

        $o2 = new Occurrence();
        $o2->setPosition(['lat' => 43.611, 'lng' => 3.877]);
        $o2->setAnecdotes('Un pin solitaire');
        $o2->setTaxon([
            'taxon_repository' => 'bdtfx', 'name_id' => 49667,
            'taxonomic_id' => 49667, 'espece' => 'Pinus nigra',
            'genre' => 'Pinus', 'famille' => 'Pinaceae',
        ]);
        $o2->setCardTag('SmartFloreBDTFXnt49667');
        $o2->setUserId(self::TEST_USER_ID);
        $t->addOccurrence($o2);
        $manager->persist($o2);
    }

    private function createSentierEnAttente(ObjectManager $manager): void
    {
        $t = new Sentier();
        $t->setNom('Jardin Secret');
        $t->setAuthorId(self::TEST_USER_ID);
        $t->setAuteur('Test User');
        $t->setAuteurEmail(self::TEST_USER_EMAIL);
        $t->setStatus('En attente');
        $t->setPosition(['start' => ['lat' => 44.0, 'lng' => 4.0], 'end' => ['lat' => 44.0, 'lng' => 4.0]]);
        $t->setPmr(0);
        $t->setMeilleuresSaisons([false, true, true, false]);
        $t->setPathLength(300);
        $t->setOccurrencesCount(1);
        $t->setNbTaxons(1);
        $t->setDetails('https://front.test/trail/jardin-secret');
        $t->setDateCreation(new DateTime('2024-03-10'));
        $manager->persist($t);

        $o = new Occurrence();
        $o->setPosition(['lat' => 44.001, 'lng' => 4.001]);
        $o->setAnecdotes('Rosier ancien');
        $o->setTaxon([
            'taxon_repository' => 'bdtfx', 'name_id' => 12345,
            'taxonomic_id' => 12345, 'espece' => 'Rosa gallica',
            'genre' => 'Rosa', 'famille' => 'Rosaceae',
        ]);
        $o->setCardTag('SmartFloreBDTFXnt12345');
        $o->setUserId(self::TEST_USER_ID);
        $t->addOccurrence($o);
        $manager->persist($o);
    }

    private function createSentierBrouillon(ObjectManager $manager): void
    {
        $t = new Sentier();
        $t->setNom('Chemin des Dames');
        $t->setAuthorId(self::TEST_USER_ID);
        $t->setAuteur('Test User');
        $t->setAuteurEmail(self::TEST_USER_EMAIL);
        $t->setStatus(null);
        $t->setPosition(['start' => ['lat' => 45.0, 'lng' => 5.0], 'end' => ['lat' => 45.0, 'lng' => 5.0]]);
        $t->setPmr(-1);
        $t->setPathLength(0);
        $t->setDateCreation(new DateTime('2024-05-20'));
        $manager->persist($t);
    }

    private function createSentierSupprime(ObjectManager $manager): void
    {
        $t = new Sentier();
        $t->setNom('Vallée Perdue');
        $t->setAuthorId('other_user');
        $t->setAuteur('Other Author');
        $t->setAuteurEmail('other@test.org');
        $t->setStatus('Validé');
        $t->setPosition(['start' => ['lat' => 46.0, 'lng' => 6.0], 'end' => ['lat' => 46.0, 'lng' => 6.0]]);
        $t->setPmr(1);
        $t->setPathLength(1000);
        $t->setDateCreation(new DateTime('2023-01-01'));
        $t->setDatePublication(new DateTime('2023-02-01'));
        $t->setDateSuppression(new DateTime('2024-01-01'));
        $t->setDetails('https://front.test/trail/vallee-perdue');
        $manager->persist($t);
    }

    private function createSentierComplet(ObjectManager $manager): void
    {
        $t = new Sentier();
        $t->setNom('Montagne Bleue');
        $t->setAuthorId(self::TEST_USER_ID);
        $t->setAuteur('Test User');
        $t->setAuteurEmail(self::TEST_USER_EMAIL);
        $t->setStatus('Validé');
        $t->setPosition(['start' => ['lat' => 47.0, 'lng' => 7.0], 'end' => ['lat' => 47.001, 'lng' => 7.001]]);
        $t->setPmr(1);
        $t->setMeilleuresSaisons([true, true, true, false]);
        $t->setPathLength(1500);
        $t->setOccurrencesCount(2);
        $t->setNbTaxons(2);
        $t->setDetails('https://front.test/trail/montagne-bleue');
        $t->setDateCreation(new DateTime('2024-01-01'));
        $t->setDatePublication(new DateTime('2024-02-01'));

        $o1 = new Occurrence();
        $o1->setPosition(['lat' => 47.0002, 'lng' => 7.0002]);
        $o1->setAnecdotes('Érable centenaire');
        $o1->setTaxon([
            'taxon_repository' => 'bdtfx', 'name_id' => 141,
            'taxonomic_id' => 141, 'espece' => 'Acer campestre',
            'genre' => 'Acer', 'famille' => 'Sapindaceae',
        ]);
        $o1->setCardTag('SmartFloreBDTFXnt141');
        $o1->setUserId(self::TEST_USER_ID);

        $i1 = new Image();
        $i1->setUrl('https://api.test/img:0001O');
        $i1->setMini('https://api.test/img:0001CXS');
        $i1->setAuthor('Photo Test');
        $i1->setCelImageId(1001);
        $o1->addImage($i1);

        $i2 = new Image();
        $i2->setUrl('https://api.test/img:0002O');
        $i2->setMini('https://api.test/img:0002CXS');
        $i2->setAuthor('Photo Test 2');
        $i2->setCelImageId(1002);
        $o1->addImage($i2);

        $t->addOccurrence($o1);

        $o2 = new Occurrence();
        $o2->setPosition(['lat' => 47.0008, 'lng' => 7.0008]);
        $o2->setAnecdotes('Hêtre majestueux');
        $o2->setTaxon([
            'taxon_repository' => 'bdtfx', 'name_id' => 36777,
            'taxonomic_id' => 36777, 'espece' => 'Fagus sylvatica',
            'genre' => 'Fagus', 'famille' => 'Fagaceae',
        ]);
        $o2->setCardTag('SmartFloreBDTFXnt36777');
        $o2->setUserId(self::TEST_USER_ID);
        $t->addOccurrence($o2);

        $p = new Path();
        $p->setType('LineString');
        $p->setCoordinates([
            ['lat' => 47.0, 'lng' => 7.0],
            ['lat' => 47.0005, 'lng' => 7.0005],
            ['lat' => 47.001, 'lng' => 7.001],
        ]);
        $t->setChemin($p);

        $manager->persist($p);
        $manager->persist($o1);
        $manager->persist($i1);
        $manager->persist($i2);
        $manager->persist($o2);
        $manager->persist($t);
    }

    private function createFiches(ObjectManager $manager): void
    {
        $f1 = new Fiche();
        $f1->setTag('SmartFloreBDTFXnt' . self::FICHE_BDTFX_NT);
        $f1->setNt((string) self::FICHE_BDTFX_NT);
        $f1->setReferentiel('bdtfx');
        $f1->setDescription('Une belle plante méditerranéenne');
        $f1->setUsages('Usage médicinal traditionnel');
        $f1->setEcologie('Garrigues et maquis');
        $f1->setProprietaire(self::TEST_USER_EMAIL);
        $f1->setUser(self::TEST_USER_ID);
        $f1->setDerniereVersion(true);
        $f1->setDateModification(new DateTime('2024-06-15'));
        $manager->persist($f1);

        $f2 = new Fiche();
        $f2->setTag('SmartFloreBDTFXnt' . self::FICHE_BDTFX_NT);
        $f2->setNt((string) self::FICHE_BDTFX_NT);
        $f2->setReferentiel('bdtfx');
        $f2->setDescription('Version ancienne');
        $f2->setProprietaire('Ancien Auteur');
        $f2->setUser('old_user');
        $f2->setDerniereVersion(false);
        $f2->setDateModification(new DateTime('2023-01-01'));
        $manager->persist($f2);
    }

    private function createFavoris(ObjectManager $manager): void
    {
        $f = new Favoris();
        $f->setUserId(self::TEST_USER_ID);
        $f->setUserEmail(self::TEST_USER_EMAIL);
        $f->setReferentiel('bdtfx');
        $f->setTaxonId(141);
        $f->setScientificName('Acer campestre L.');
        $manager->persist($f);
    }

    private function createPing(ObjectManager $manager): void
    {
        $p = new Ping();
        $p->setIsLogged(false);
        $p->setIsLocated(true);
        $p->setIsOnline(true);
        $p->setTrail(self::TRAIL_DRAFT_ID);
        $p->setDate((new DateTime('yesterday'))->format('Y-m-d H:i:s'));
        $p->setFromWebsite(false);
        $p->setIp('10.10.10.10');
        $p->setDistanceFromTrail(50);
        $manager->persist($p);
    }
}
