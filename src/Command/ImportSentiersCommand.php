<?php

namespace App\Command;

use App\Entity\Favoris;
use App\Entity\Occurrence;
use App\Entity\Path;
use App\Entity\Sentier;
use App\Model\Taxon;
use App\Service\AnnuaireService;
use App\Service\CreateTrailService;
use App\Service\EfloreService;
use App\Service\FicheService;
use App\Service\importService;
use App\Service\SharedService;
use App\Service\TrailsService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ImportSentiersCommand extends Command
{
    protected static $defaultName = 'app:import:sentiers';
    protected static $defaultDescription = 'Import trails from old Smart\'Flore service';

    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private CreateTrailService $createTrailService;
    private SharedService $sharedService;
    private FicheService $ficheService;
    private EfloreService $efloreService;
    private importService $importService;
    private AnnuaireService $annuaire;

    public function __construct(Connection $connection,
                                EntityManagerInterface $entityManager,
                                SerializerInterface $serializer,
                                CreateTrailService $createTrailService,
                                SharedService $sharedService,
                                FicheService $ficheService,
                                EfloreService $efloreService,
                                importService $importService,
                                AnnuaireService $annuaire
    )
    {
        parent::__construct();
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->createTrailService = $createTrailService;
        $this->sharedService = $sharedService;
        $this->ficheService = $ficheService;
        $this->efloreService = $efloreService;
        $this->importService = $importService;
        $this->annuaire = $annuaire;
    }

    protected function configure(): void
    {
//        $this
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
//        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('import-trails');
        $today = new \DateTime("now");

        $timeStarted = $today->format('d-m-Y H:i:s');
        $io->title(sprintf('script started at %s .', ($timeStarted)));

        $this->connection->executeStatement('SET SESSION group_concat_max_len = 1000000');
        $trailsNames = $this->connection->fetchAllAssociative(
            'SELECT value FROM `eFloreRedaction_triples` WHERE property = "smartFlore.evenements.sentiers.ajout"'
        );

        $trails = $this->connection->fetchAllAssociative(
            'SELECT t1.resource AS nom,
                       ANY_VALUE(t2.value) AS pmr,
                       ANY_VALUE(t3.value) AS statut,
                       ANY_VALUE(t4.value) AS meilleures_saisons,
                       ANY_VALUE(t5.value) AS position,
                       ANY_VALUE(t6.value) AS auteur,
                       ANY_VALUE(t7.value) AS date_creation,
                       ANY_VALUE(t8.value) AS date_modification,
                       ANY_VALUE(t9.value) AS date_suppression,
                       ANY_VALUE(t10.value) AS dessin,
                       GROUP_CONCAT(t11.value SEPARATOR "||") AS fiches,
                        ANY_VALUE(t12.value) AS images
                    FROM `eFloreRedaction_triples` t1
                    LEFT JOIN `eFloreRedaction_triples` t2 ON t1.resource = t2.resource
                    AND t2.property = "smartFlore.sentiers.pmr"
                    LEFT JOIN `eFloreRedaction_triples` t3 ON t1.resource = t3.resource
                    AND t3.property = "smartFlore.sentiers.etat"
                    LEFT JOIN `eFloreRedaction_triples` t4 ON t1.resource = t4.resource
                    AND t4.property = "smartFlore.sentiers.meilleures_saisons"
                    LEFT JOIN `eFloreRedaction_triples` t5 ON t1.resource = t5.resource
                    AND t5.property = "smartFlore.sentiers.localisation"
                    LEFT JOIN `eFloreRedaction_triples` t6 ON t1.resource = t6.resource
                    AND t6.property = "smartFlore.sentiers"
                    LEFT JOIN `eFloreRedaction_triples` t7 ON t1.resource = t7.resource
                    AND t7.property = "smartFlore.sentiers.date_creation"
                    LEFT JOIN `eFloreRedaction_triples` t8 ON t1.resource = t8.resource
                    AND t8.property = "smartFlore.sentiers.date_derniere_modif"
                    LEFT JOIN `eFloreRedaction_triples` t9 ON t1.resource = t9.resource
                    AND t9.property = "smartFlore.sentiers.date_suppression"
                    LEFT JOIN `eFloreRedaction_triples` t10 ON t1.resource = t10.resource
                    AND t10.property = "smartFlore.sentiers.dessin"
                    LEFT JOIN `eFloreRedaction_triples` t11 ON t1.resource = t11.resource
                    AND t11.property = "smartFlore.sentiers.fiche"
                    LEFT JOIN `eFloreRedaction_triples` t12 ON t1.resource = t12.resource
                    AND t12.property = "smartFlore.sentiers.fiches_illustrations"
                    WHERE t1.property = "smartFlore.sentiers"
                      AND t1.resource NOT LIKE "%deleted_at_%"
                      GROUP BY t1.resource
                    ;'
        );

        $users = [];
        $taxons = [];
        foreach ($trails as $trail) {
            // On récupère l'auteur et son email
            foreach ($trailsNames as $trailNameData) {
                $data = json_decode($trailNameData['value'], true);
                if (isset($data['titre']) && $data['titre'] === $trail['nom']) {
                    $trail['auteur'] = $data['utilisateur'];
                    $trail['auteur_email'] = $data['utilisateur_courriel'];
                    break;
                }
            }

            $positionJson = json_decode($trail['position'] ?? '', true);
            $sentierCoords = $positionJson['sentier'] ?? null;
            $individusWithPosition = $positionJson['individus'] ?? [];

            $individus = explode('||', $trail['fiches']);;
            $individusFusionnes = $this->importService->fusionneFichesAvecEtSansLocalisation($individus, $individusWithPosition);

            $sentier = new Sentier();
            $sentier = $this->importService->creerSentier($trail, $sentier, $sentierCoords);
            $sentier = $this->importService->ajouterCheminAuSentier($trail, $sentier);

            // On cherche l'id de l'utilisateur
            if ($sentier->getAuteurEmail()) {
                //On cherche d'abord dans la table user avant de chercher dans l'annuaire pour éviter trop de requêtes
                $emails = array_column($users, 'email');
                $index = array_search($sentier->getAuteurEmail(), $emails);
                if ($index !== false) {
                    $userId = $users[$index]['id'];
                    $user = $users[$index];
                    $sentier->setAuthorId($userId);
                } else {
                    $userId = $this->annuaire->findUserIdByEmail($sentier->getAuteurEmail());
                    if ($userId) {
                        $user = [
                            'email' => $sentier->getAuteurEmail(),
                            'id' => $userId,
                        ];
                        $users[] = $user;
                        $sentier->setAuthorId($userId);
                    }
                }
            }

            if (count($individusFusionnes) > 0) {
                foreach ($individusFusionnes as $individu) {
                    $occurrence = new Occurrence();
                    $occurrence->setPosition([
                        'lat' => $individu['lat'],
                        'lng' => $individu['lng'],
                    ]);
                    $occurrence->setCardTag($individu['ficheTag']);
                    $occurrence->setUserId("0");

                    if ($userId) {
                        $occurrence->setUserId($userId);
                    }

                    $this->importService->addImagesToOccurrence($trail, $individu, $occurrence);
                    $taxons = $this->importService->addTaxonToOccurrence($occurrence, $individu, $trail, $taxons);

                    $occurrence->setSentier($sentier);
                    $sentier->addOccurrence($occurrence);
                }
            }

            $this->createTrailService->addNbTaxonsToTrail($sentier);

            $this->entityManager->persist($sentier);
            $this->entityManager->flush();

            $sentier = $this->sharedService->addDetailToTrail($sentier);

            $this->entityManager->persist($sentier);
            $this->entityManager->flush();
        }

        // Import des favoris
        foreach ($users as $user) {
            $userFavorisListFromDb = $this->getUserFavoris($user);
            if ($userFavorisListFromDb) {
                foreach ($userFavorisListFromDb as $userFavoris) {
                    $infos = $this->importService->findUserFavoris($user, $taxons, $userFavoris);
                    $taxons = $infos[0];
                    $favoris = $infos[1];

                    $this->entityManager->persist($favoris);
                    $this->entityManager->flush();
                }
            }
        }

        $event = $stopwatch->stop('import-trails');
        $end = new \DateTime("now");
        $timeFinished = $end->format('d-m-Y H:i:s');
        $io->success(['Sentiers, favoris et occurrences importées avec succès.',
            sprintf('Job started at %s, Job finished at %s. Elapsed time:%.2f m, Consumed memory: %.2f MB ',
                $timeStarted,
                $timeFinished,
                ($event->getDuration())/60000,
                $event->getMemory() / (1024 ** 2)
            )
        ]);

        return Command::SUCCESS;
    }

    private function getUserFavoris(array $user): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT value FROM `eFloreRedaction_triples` WHERE property = "smartFlore.favoris.fiche" AND resource = "'.$user['email'].'"'
        );
    }
}
