<?php

namespace App\Service;

//use App\Service\ImageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Service de cache fichier JSON pour les sentiers, taxons et fiches.
 *
 * Structure des dossiers :
 *   {CACHE_PATH}/sentiers/trail-{id}.json
 *   {CACHE_PATH}/taxons/taxon{referentiel}nn{num_nom}.json
 *   {CACHE_PATH}/fiches/SmartFlore{referentiel}nt{taxonomic_id}.json
 */
class CacheFileService
{
    private string $cachePath;
    private Filesystem $filesystem;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
//    private ImageService $imageService;

    public function __construct(
        string $cachePath,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->cachePath = rtrim($cachePath, '/');
        $this->filesystem = new Filesystem();
        $this->serializer = $serializer;
        $this->logger = $logger;

        $this->ensureCacheDirectoriesExist();
    }

    // -------------------------------------------------------------------------
    // Sentiers
    // -------------------------------------------------------------------------

    public function getTrailCachePath(int $trailId): string
    {
        return $this->cachePath . '/sentiers/trail-' . $trailId . '.json';
    }

    /**
     * Retourne les données du sentier depuis le cache, ou null si absent/invalide.
     */
    public function getTrail(int $trailId): ?array
    {
        $path = $this->getTrailCachePath($trailId);

        return $this->readJsonFile($path);
    }

    /**
     * Sérialise et écrit le sentier en cache.
     *
     * @param object $trail      Entité Sentier
     * @param array  $groups     Groupes de sérialisation Symfony
     */
    public function saveTrail(int $trailId, object $trail, array $groups = ['show_trail']): bool
    {
        $path = $this->getTrailCachePath($trailId);
        $json = $this->serializer->serialize($trail, 'json', ['groups' => $groups]);

        return $this->writeJsonFile($path, $json);
    }

    /**
     * Supprime le fichier cache du sentier.
     */
    public function deleteTrail(int $trailId): bool
    {
        $path = $this->getTrailCachePath($trailId);

        return $this->deleteFile($path);
    }

    // -------------------------------------------------------------------------
    // Taxons
    // -------------------------------------------------------------------------

    public function getTaxonCachePath(string $referentiel, int $numNom): string
    {
        return $this->cachePath . '/taxons/taxon' . strtolower($referentiel) . 'nn' . $numNom . '.json';
    }

    /**
     * Retourne les données du taxon depuis le cache, ou null si absent/invalide.
     */
    public function getTaxon(string $referentiel, int $numNom): ?array
    {
        $path = $this->getTaxonCachePath($referentiel, $numNom);

        return $this->readJsonFile($path);
    }

    /**
     * Sérialise et écrit le taxon en cache.
     *
     * @param object $taxon  Instance de Taxon
     * @param array  $groups Groupes de sérialisation Symfony
     */
    public function saveTaxon(string $referentiel, int $numNom, object $taxon, array $groups = ['show_taxon', 'full_images']): bool
    {
        $path = $this->getTaxonCachePath($referentiel, $numNom);
        $json = $this->serializer->serialize($taxon, 'json', ['groups' => $groups]);

        return $this->writeJsonFile($path, $json);
    }

    /**
     * Supprime le fichier cache du taxon.
     */
    public function deleteTaxon(string $referentiel, int $numNom): bool
    {
        $path = $this->getTaxonCachePath($referentiel, $numNom);

        return $this->deleteFile($path);
    }

    // -------------------------------------------------------------------------
    // Fiches
    // -------------------------------------------------------------------------

    public function getFicheCachePath(string $referentiel, int $taxonomicId): string
    {
        return $this->cachePath . '/fiches/SmartFlore' . strtoupper($referentiel) . 'nt' . $taxonomicId . '.json';
    }

    /**
     * Retourne les données de la fiche depuis le cache, ou null si absent/invalide.
     */
    public function getFiche(string $referentiel, int $taxonomicId): ?array
    {
        $path = $this->getFicheCachePath($referentiel, $taxonomicId);

        return $this->readJsonFile($path);
    }

    /**
     * Sérialise et écrit la fiche en cache.
     *
     * @param object $fiche  Entité Fiche
     * @param array  $groups Groupes de sérialisation Symfony
     */
    public function saveFiche(string $referentiel, int $taxonomicId, object $fiche, array $groups = ['show_fiche']): bool
    {
        $path = $this->getFicheCachePath($referentiel, $taxonomicId);
        $json = $this->serializer->serialize($fiche, 'json', ['groups' => $groups]);

        return $this->writeJsonFile($path, $json);
    }

    /**
     * Supprime le fichier cache de la fiche.
     */
    public function deleteFiche(string $referentiel, int $taxonomicId): bool
    {
        $path = $this->getFicheCachePath($referentiel, $taxonomicId);

        return $this->deleteFile($path);
    }

    // -------------------------------------------------------------------------
    // Primitives privées
    // -------------------------------------------------------------------------

    /**
     * Lit un fichier JSON et retourne le tableau décodé, ou null en cas d'erreur.
     */
    private function readJsonFile(string $path): ?array
    {
        if (!$this->filesystem->exists($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->logger->warning('FileCacheService: impossible de lire le fichier cache.', ['path' => $path]);
            return null;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('FileCacheService: JSON invalide dans le cache, fichier ignoré.', [
                'path' => $path,
                'error' => json_last_error_msg(),
            ]);
            // Fichier corrompu : on le supprime pour éviter de servir des données invalides
            $this->deleteFile($path);
            return null;
        }

        return $data;
    }

    /**
     * Écrit un contenu JSON dans un fichier de façon atomique (fichier temporaire + rename).
     */
    private function writeJsonFile(string $path, string $json): bool
    {
        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                $this->filesystem->mkdir($dir, 0755);
            }

            // Écriture atomique : on écrit dans un fichier temporaire puis on le déplace
            $tmpPath = $path . '.tmp.' . uniqid('', true);
            $this->filesystem->dumpFile($tmpPath, $json);
            $this->filesystem->rename($tmpPath, $path, true);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('FileCacheService: erreur lors de l\'écriture du cache.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Supprime un fichier cache s'il existe.
     */
    private function deleteFile(string $path): bool
    {
        if (!$this->filesystem->exists($path)) {
            return true; // Déjà absent, considéré comme succès
        }

        try {
            $this->filesystem->remove($path);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('FileCacheService: erreur lors de la suppression du cache.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Crée les répertoires de cache s'ils n'existent pas encore.
     */
    private function ensureCacheDirectoriesExist(): void
    {
        $dirs = [
            $this->cachePath,
            $this->cachePath . '/sentiers',
            $this->cachePath . '/taxons',
            $this->cachePath . '/fiches',
        ];

        foreach ($dirs as $dir) {
            clearstatcache(true, $dir);
            if (!is_dir($dir)) {
                try {
                    // Le composant Filesystem de Symfony crée les dossiers récursivement par défaut
                    $this->filesystem->mkdir($dir, 0755);
                } catch (\Exception $e) {
                    $this->logger->error('CacheFileService: impossible de créer le répertoire cache.', [
                        'dir' => $dir,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
// Trails List (smartflore_trails.json)
// -------------------------------------------------------------------------

    public function getTrailsListCachePath(): string
    {
        return $this->cachePath . '/smartflore_trails.json';
    }

    /**
     * Retourne la liste des sentiers validés depuis le cache, ou null si absente/invalide.
     * @return array<int, array<string, mixed>>|null
     */
    public function getTrailsList(): ?array
    {
        return $this->readJsonFile($this->getTrailsListCachePath());
    }

    /**
     * Construit et écrit la liste complète des sentiers validés en cache.
     * Chaque sentier est également sauvegardé individuellement via saveTrail().
     *
     * @param object[] $trails  Entités Sentier avec status='Validé'
     * @param array<string> $groups Groupes de sérialisation
     */
    public function saveTrailsList(array $trails, array $groups = ['list_trail']): bool
    {
        // Sauvegarde individuelle de chaque sentier
        foreach ($trails as $trail) {
            // Compatibilité avec ancien service,
            //A voir si c'est encore utilisé, charger imageService fait planté
//            $this->imageService->findOneImagePlease($trail);
            $this->saveTrail($trail->getId(), $trail, ['show_trail']);
        }

        $json = $this->serializer->serialize($trails, 'json', ['groups' => $groups]);

        return $this->writeJsonFile($this->getTrailsListCachePath(), $json);
    }

    /**
     * Supprime le fichier de liste des sentiers.
     */
    public function deleteTrailsList(): bool
    {
        return $this->deleteFile($this->getTrailsListCachePath());
    }
}