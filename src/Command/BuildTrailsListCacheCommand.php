<?php

namespace App\Command;

use App\Repository\SentierRepository;
use App\Service\CacheFileService;
use App\Service\EfloreService;
use App\Service\SharedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildTrailsListCacheCommand extends Command
{
    protected static $defaultName = 'app:cache:build-trails-list';
    protected static $defaultDescription = 'Construit le cache JSON de la liste des sentiers validés (smartflore_trails.json), ainsi que le cache des taxons et fiches de ses sentiers';
    private CacheFileService $cacheFile;
    private SentierRepository $sentierRepository;
    private EfloreService $efloreService;
    private SharedService $sharedService;
    private EntityManagerInterface $em;

    public function __construct(
        SentierRepository $sentierRepository,
        CacheFileService $cacheFile,
        EfloreService $efloreService,
        SharedService $sharedService,
        EntityManagerInterface $em
    ) {
        $this->em = $em;
        $this->sharedService = $sharedService;
        $this->efloreService = $efloreService;
        $this->sentierRepository = $sentierRepository;
        $this->cacheFile = $cacheFile;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Construction du cache de la liste des sentiers');

        $trails = $this->sentierRepository->findBy(
            ['status' => 'Validé', 'date_suppression' => null],
            ['nom' => 'ASC']
        );

        if (empty($trails)) {
            $io->warning('Aucun sentier validé trouvé.');
            return Command::SUCCESS;
        }

        $total = count($trails);
        $io->progressStart($total);
        $errors = [];

        foreach ($trails as $trail) {
            try {
                // 1. Cache du sentier individuel
                $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

                // 2. Cache taxons et fiches pour chaque occurrence
                foreach ($trail->getOccurrences() as $occurrence) {
                    $taxonData = $occurrence->getTaxon();

                    if (!isset($taxonData['taxon_repository'], $taxonData['name_id'])) {
                        continue;
                    }

                    $referentiel = $taxonData['taxon_repository'];
                    $nameId = (int) $taxonData['name_id'];
                    $acceptedId = isset($taxonData['accepted_scientific_name_id'])
                        ? (int) $taxonData['accepted_scientific_name_id']
                        : null;

                    // Cache taxon si absent
                    if ($this->cacheFile->getTaxon($referentiel, $nameId) === null) {
                        try {
                            $taxon = $this->efloreService->getTaxon($referentiel, $nameId, true);
                            if ($taxon) {
                                $this->cacheFile->saveTaxon($referentiel, $nameId, $taxon, ['show_taxon', 'full_images']);
                            }
                        } catch (\Exception $e) {
                            $errors[] = sprintf('Trail %d,Taxon %s/%d : %s', $trail->getId(), $referentiel, $nameId, $e->getMessage());
                        }
                    }

                    // Cache fiche si absent (utilise accepted_scientific_name_id = num_tax)
                    if ($acceptedId !== null && $this->cacheFile->getFiche($referentiel, $acceptedId) === null) {
                        try {
                            $fiche = $this->sharedService->chercherFiche($referentiel, $acceptedId);
                            if ($fiche) {
                                $this->cacheFile->saveFiche($referentiel, $acceptedId, $fiche, ['list_fiche']);
                            }
                        } catch (\Exception $e) {
                            $errors[] = sprintf('Fiche %s/%d : %s', $referentiel, $acceptedId, $e->getMessage());
                        }
                    }
                }

                // Libère la mémoire Doctrine après chaque sentier
                $this->em->detach($trail);

            } catch (\Exception $e) {
                $errors[] = sprintf('Sentier %d (%s) : %s', $trail->getId(), $trail->getNom(), $e->getMessage());
            }

            $io->progressAdvance();
        }

        // 3. Cache de la liste complète
        $trailsForList = $this->sentierRepository->findBy(
            ['status' => 'Validé', 'date_suppression' => null],
            ['nom' => 'ASC']
        );
        $success = $this->cacheFile->saveTrailsList($trailsForList, ['list_trail']);

        $io->progressFinish();

        if (!empty($errors)) {
            $io->warning(sprintf('%d erreur(s) rencontrée(s) :', count($errors)));
            foreach ($errors as $error) {
                $io->text('  - ' . $error);
            }
        }

        if (!$success) {
            $io->error('Erreur lors de l\'écriture de smartflore_trails.json');
            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d sentiers mis en cache (dont %d erreur(s) non bloquante(s)).',
            $total,
            count($errors)
        ));

        return Command::SUCCESS;
    }
}
