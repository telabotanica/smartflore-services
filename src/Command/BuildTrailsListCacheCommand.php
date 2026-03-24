<?php

namespace App\Command;

use App\Repository\SentierRepository;
use App\Service\CacheFileService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildTrailsListCacheCommand extends Command
{
    protected static $defaultName = 'app:cache:build-trails-list';
    protected static $defaultDescription = 'Construit le cache JSON de la liste des sentiers validés (smartflore_trails.json)';
    private CacheFileService $cacheFile;
    private SentierRepository $sentierRepository;

    public function __construct(
        SentierRepository $sentierRepository,
        CacheFileService $cacheFile
    ) {
        $this->sentierRepository = $sentierRepository;
        $this->cacheFile = $cacheFile;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
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

        $io->progressStart(count($trails));

        $success = $this->cacheFile->saveTrailsList($trails, ['list_trail']);

        $io->progressFinish();

        if (!$success) {
            $io->error('Erreur lors de l\'écriture du fichier cache smartflore_trails.json');
            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d sentiers écrits dans smartflore_trails.json (+ cache individuel de chaque sentier)',
            count($trails)
        ));

        return Command::SUCCESS;
    }
}
