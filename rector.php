<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    // ============================================================================
    // PRE-UPGRADE CHECKLIST (execute these BEFORE running rector process):
    // ============================================================================
    // 1. Verify PHP 7.4: php -v
    // 2. Run tests baseline: ./bin/phpunit
    // 3. Clear cache: rm -rf var/cache/*
    // 4. Warmup cache: symfony console cache:warmup
    // 5. IMPORTANT: Step 4 generates var/cache/dev/App_KernelDevDebugContainer.xml
    //    required for withSymfonyContainerXml() below
    // ============================================================================

    // Define paths to analyze
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/bin',
        __DIR__ . '/config',
    ])

    // Exclude vendor and cache directories
    ->withSkip([
        __DIR__ . '/var',
        __DIR__ . '/vendor',
    ])

//    ->withImportNames(removeUnusedImports: true) //Remove Unused Imports

    // ============================================================================
    // PHP VERSION: Auto-detect from composer.json (targets PHP 8.1)
    // ============================================================================
    ->withPhpSets()
//    ->withPhpSets(php81: true) // Pour php > 8
//    ->withPhpVersion(PhpVersion::PHP_81)

    // ============================================================================
    // CONSERVATIVE REFACTORING: Type Declarations only
    // (excludes: deadCode, codeQuality, codingStyle for minimal impact)
    // Use withSets() instead of withPreparedSets() for PHP 7.4 compatibility
    // ============================================================================
    ->withSets([
        \Rector\Set\ValueObject\SetList::TYPE_DECLARATION,
    ])

    // ============================================================================
    // SYMFONY 6.4 INTEGRATION: Code quality + best practices
    // Handles: annotations→attributes, route changes, form updates, etc.
    // ============================================================================
    ->withSets([
        SymfonySetList::SYMFONY_CODE_QUALITY,
    ])

    // ============================================================================
    // SYMFONY CONTAINER XML: Advanced service/form refactoring
    // PREREQUISITE: Must run 'symfony console cache:warmup' BEFORE rector
    // This enables intelligent refactoring of DI configuration
    // ============================================================================
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')

    // =============================================================================
    // Attributes (migrate your annotations to native PHP 8.0 attributes)
    // =============================================================================
    ->withAttributesSets()
//    ->withAttributesSets(symfony: true, doctrine: true) //limit to specific groups

    // =============================================================================
    // Composer Based Sets
    // Projects like Symfony, Doctrine, Twig or Laravel have lots of versions.
    // Instead of adding dozens of sets for each of those, you can make use of composer-based set resolution
    // =============================================================================
//    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)

    // ============================================================================
    // POST-UPGRADE VERIFICATION STEPS (execute these AFTER rector finishes):
    // ============================================================================
    // 1. Dry-run first: ./vendor/bin/rector process --dry-run
    // 2. Review proposed changes carefully
    // 3. Apply changes: ./vendor/bin/rector process
    // 4. Clear cache: rm -rf var/cache/*
    // 5. Warmup again: symfony console cache:warmup
    // 6. Type checking: ./vendor/bin/phpstan analyse
    // 7. Run tests: ./bin/phpunit
    // 8. Manual fixes: Address any remaining type or compatibility issues
    // 9. Update composer.json: Manually set "symfony/framework-bundle": "6.4.*"
    //                          and "php": ">=8.1"
    // 10. composer update
    // 11. Run full test suite again
    // ============================================================================
;
