<?php

namespace App\Command;

use App\Service\CacheService;
use App\Service\TrailsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheRefreshCommand extends Command
{
    protected static $defaultName = 'app:cache:refresh';
    protected static $defaultDescription = 'Add a short description for your command';

    private $cache;
    private $trails;

    public function __construct(
        string $name = null,
        CacheService $cache,
        TrailsService $trails
    ) {
        $this->cache = $cache;
        $this->trails = $trails;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('resource', InputArgument::OPTIONAL, 'Which cache should we refresh?', 'none')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $resource = $input->getArgument('resource');

        if ('none' === $resource) {
            $io->note(sprintf('Specify resource to refresh:'));
            $io->note(sprintf(' - all (trails and cards)'));
            $io->note(sprintf(' - only cards (all cards texts and info)'));
            $io->note(sprintf(' - <trail-name>'));

            return Command::INVALID;
        }

        switch ($resource) {
            case 'all':
                $this->cache->refresh();
                break;
            case 'cards':
                $trails = $this->trails->getTrails();
                foreach ($trails as $trail) {
                    $this->trails->collectOccurrencesTaxonInfos($trail, true);
                }
                break;
            default:
                $this->trails->getTrail($resource, true);
        }

        $io->success('OK! '.$resource.' cache refreshed successfully :)');

        return Command::SUCCESS;
    }
}
