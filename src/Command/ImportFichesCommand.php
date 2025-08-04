<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Fiche;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportFichesCommand extends Command
{
    protected static $defaultName = 'app:import:fiches';
    protected static $defaultDescription = 'Import fiches from old Smart\'Flore service';

    private $connection;
    private $entityManager;

    public function __construct(Connection $connection, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->entityManager = $entityManager;
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
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM eFloreRedaction_pages');

        file_put_contents('fiche_incompletes.csv', "tag,missing\n");

        foreach ($rows as $row) {
            $fiche = new Fiche();
            $fiche->setTag($row['tag']);
            if (preg_match('/nt(\d+)/i', $row['tag'], $matches)) {
                $fiche->setNt((string)$matches[1]);
            }

            if (preg_match('/(?:SmartFlore)?([A-Z]+)nt\d+/i', $row['tag'], $matches)) {
                $fiche->setReferentiel(strtolower($matches[1]));
            }

            $fiche->setDateModification((new \DateTime($row['time'])));

            $desc = $this->extractSection($row['body'], 'Description');
            $usages = $this->extractSection($row['body'], 'Usage');
            $eco = $this->extractSection($row['body'], 'écologie');
            $sources = $this->extractSection($row['body'], 'Source');
            $fiche->setDescription($desc);
            $fiche->setUsages($usages);
            $fiche->setEcologie($eco);
            $fiche->setSources($sources);

            $fiche->setProprietaire($row['owner']);
            $fiche->setUser($row['user']);

            $latest = $row['latest'] == 'Y';
            $fiche->setDerniereVersion((bool) $latest);

            // Log si fiche incomplète
            $missing = [];
            if (!$desc) $missing[] = 'description';
            if (!$usages) $missing[] = 'usages';
            if (!$eco) $missing[] = 'écologie';
            if (!$sources) $missing[] = 'source';

            if (!empty($missing)) {
                file_put_contents(
                    'fiche_incompletes.csv',
                    $row['tag'] . ',' . implode('|', $missing) . "\n",
                    FILE_APPEND
                );
            }

            $this->entityManager->persist($fiche);
        }

        $this->entityManager->flush();
        $io->success('Fiches importées avec succès.');

        return Command::SUCCESS;
    }

    private function removeAccents(string $str): string
    {
        $unwanted_array = [
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'AE', 'Ç'=>'C',
            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ð'=>'D', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ő'=>'O',
            'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ű'=>'U', 'Ý'=>'Y', 'Þ'=>'TH',
            'ß'=>'ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae',
            'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'d', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'ő'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ű'=>'u', 'ý'=>'y',
            'þ'=>'th', 'ÿ'=>'y'
        ];
        return strtr($str, $unwanted_array);
    }

    private function extractSection(string $body, string $keyword): ?string
    {
        $keyword = mb_strtolower($keyword);
        $keyword = $this->removeAccents($keyword);

        $pattern = '/
        ====+                  # Début section (>=4 signes =)
        \s*                    # Espaces optionnels
        ([^=]*?)                # Capture titre (tout sauf =)
        \s*                    # Espaces optionnels
        ====+                  # Fin section
        \s*                    # Espaces optionnels (sauts de ligne)
        (.*?)                  # Capture contenu, non-greedy
        (?=^====+[^=]*====+|\z) # Jusqu’à la prochaine section ou fin de texte
    /imsx';

        if (preg_match_all($pattern, $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $title = mb_strtolower(trim($match[1]));
                $title = $this->removeAccents($title);

                if (mb_strpos($title, $keyword) !== false) {
                    $content = trim($match[2]);
                    return $content !== '' ? $content : null;
                }
            }
        }

        return null;
    }

}
