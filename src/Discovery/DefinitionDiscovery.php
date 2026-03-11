<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Discovery;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_values;
use function class_exists;
use function dirname;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function is_subclass_of;
use function mb_ltrim;
use function mb_rtrim;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function realpath;
use function str_ends_with;
use function str_replace;
use function str_starts_with;

/**
 * Discovers breadcrumb definition classes from Composer metadata and filesystem scans.
 *
 * Discovery is the package's fallback expansion step when definitions are not
 * supplied exhaustively through configuration. It normalizes candidate paths,
 * inspects configured Composer classmaps first, then walks PSR-4 directories to
 * infer class names from file locations. Only loadable classes that implement
 * {@see BreadcrumbDefinition} survive the pipeline.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class DefinitionDiscovery
{
    /**
     * @param list<string> $classmapPaths
     *
     * The configured classmap files are consulted before PSR-4 traversal so
     * existing Composer metadata can short-circuit some discovery work.
     */
    public function __construct(
        #[Config('breadcrumbs.discovery.classmap_paths', [])]
        private array $classmapPaths,
    ) {}

    /**
     * @param list<string> $paths
     *
     * Discover definition classes from the provided root directories.
     *
     * Invalid, empty, or non-directory paths are discarded during path
     * normalization. Duplicates are removed by class name, with later discovery
     * passes overwriting earlier file-path bookkeeping while preserving the
     * final unique class list returned to callers.
     *
     * @return list<class-string>
     */
    public function discover(array $paths): array
    {
        $resolvedPaths = $this->resolvePaths($paths);

        if ($resolvedPaths === []) {
            return [];
        }

        $discovered = [];

        foreach ($this->discoverFromClassmaps($resolvedPaths) as $class => $file) {
            if (!$this->isDefinition($class, $file)) {
                continue;
            }

            $discovered[$class] = $class;
        }

        foreach ($this->discoverFromPsr4($resolvedPaths) as $class => $file) {
            if (!$this->isDefinition($class, $file)) {
                continue;
            }

            $discovered[$class] = $class;
        }

        return array_values($discovered);
    }

    /**
     * @param list<string> $paths
     *
     * Normalize discovery roots to absolute directory paths.
     *
     * Only real directories survive this stage, which prevents later classmap
     * and filesystem passes from performing redundant existence checks.
     * @return list<string>
     */
    private function resolvePaths(array $paths): array
    {
        $resolved = [];

        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            $resolvedPath = realpath($path);

            if (!is_string($resolvedPath)) {
                continue;
            }

            if (!is_dir($resolvedPath)) {
                continue;
            }

            $resolved[] = mb_rtrim(str_replace('\\', '/', $resolvedPath), '/');
        }

        return $resolved;
    }

    /**
     * @param list<string> $paths
     *
     * Filter Composer classmap entries down to classes inside the discovery roots.
     *
     * This pass trusts Composer's class-to-file mapping but still validates
     * class names and file path membership before returning candidates.
     *
     * @return array<class-string, string>
     */
    private function discoverFromClassmaps(array $paths): array
    {
        $definitions = [];

        foreach ($this->loadClassmaps() as $class => $file) {
            if (!is_string($class)) {
                continue;
            }

            if (!is_string($file)) {
                continue;
            }

            if ($file === '') {
                continue;
            }

            if (!$this->isValidClassName($class)) {
                continue;
            }

            $normalizedFile = str_replace('\\', '/', $file);

            if (!$this->isInDiscoveryPaths($normalizedFile, $paths)) {
                continue;
            }

            /** @var class-string $class */
            $definitions[$class] = $file;
        }

        return $definitions;
    }

    /**
     * Load and merge the configured Composer classmap files.
     *
     * Each file is expected to return an array matching Composer's generated
     * classmap format. Invalid files are skipped silently so discovery can
     * continue even when optional classmap inputs are absent.
     *
     * @return array<mixed, mixed>
     */
    private function loadClassmaps(): array
    {
        $mappings = [];

        foreach ($this->classmapPaths as $classmapPath) {
            if ($classmapPath === '') {
                continue;
            }

            if (!is_file($classmapPath)) {
                continue;
            }

            /** @var mixed $classmap */
            $classmap = require $classmapPath;

            if (!is_array($classmap)) {
                continue;
            }

            foreach ($classmap as $class => $file) {
                $mappings[$class] = $file;
            }
        }

        return $mappings;
    }

    /**
     * @param list<string> $paths
     *
     * Walk PSR-4 directories under the discovery roots and infer classes by path.
     *
     * This is broader and potentially more expensive than the classmap pass,
     * which is why it runs second and only after a PSR-4 map is available.
     *
     * @return array<class-string, string>
     */
    private function discoverFromPsr4(array $paths): array
    {
        $definitions = [];
        $psr4 = $this->loadPsr4Map();

        if ($psr4 === []) {
            return [];
        }

        foreach ($paths as $path) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $filePath = str_replace('\\', '/', $file->getPathname());
                $class = $this->classFromPsr4Path($filePath, $psr4);

                if ($class === null) {
                    continue;
                }

                $definitions[$class] = $file->getPathname();
            }
        }

        return $definitions;
    }

    /**
     * @param array<string, list<string>> $psr4
     *
     * Infer the fully qualified class name for a PHP file using Composer PSR-4 mappings.
     *
     * Returns `null` when the file does not belong to any registered namespace
     * root or when the inferred class name would be syntactically invalid.
     *
     * @return null|class-string
     */
    private function classFromPsr4Path(string $filePath, array $psr4): ?string
    {
        $normalizedFile = str_replace('\\', '/', $filePath);

        foreach ($psr4 as $prefix => $directories) {
            foreach ($directories as $directory) {
                if ($directory === '') {
                    continue;
                }

                $normalizedDirectory = mb_rtrim(str_replace('\\', '/', $directory), '/').'/';

                if (!str_starts_with($normalizedFile, $normalizedDirectory)) {
                    continue;
                }

                $relative = mb_substr($normalizedFile, mb_strlen($normalizedDirectory));

                if (!str_ends_with($relative, '.php')) {
                    continue;
                }

                $classRelative = mb_substr($relative, 0, -4);

                if ($classRelative === '') {
                    continue;
                }

                $class = mb_ltrim($prefix, '\\').str_replace('/', '\\', $classRelative);

                if (!$this->isValidClassName($class)) {
                    continue;
                }

                /** @var class-string $class */
                return $class;
            }
        }

        return null;
    }

    /**
     * @param list<string> $paths
     *
     * Determine whether a file resides under one of the normalized discovery roots.
     */
    private function isInDiscoveryPaths(string $filePath, array $paths): bool
    {
        $normalized = str_replace('\\', '/', $filePath);

        foreach ($paths as $path) {
            $prefix = mb_rtrim($path, '/').'/';

            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Confirm that a candidate class can be loaded and implements the definition contract.
     *
     * Files are required lazily when the class is not already loaded so
     * discovery can validate classes inferred from filesystem paths.
     */
    private function isDefinition(string $class, string $file): bool
    {
        if (!class_exists($class, false)) {
            if (!is_file($file)) {
                return false;
            }

            require_once $file;
        }

        return class_exists($class, false) && is_subclass_of($class, BreadcrumbDefinition::class);
    }

    /**
     * Load Composer's generated PSR-4 namespace map from the local vendor tree.
     *
     * Missing or malformed Composer metadata is treated as "no PSR-4 sources
     * available" rather than an exceptional condition.
     *
     * @return array<string, list<string>>
     */
    private function loadPsr4Map(): array
    {
        $psr4MapPath = dirname(__DIR__, 2).'/vendor/composer/autoload_psr4.php';

        if (!is_file($psr4MapPath)) {
            return [];
        }

        /** @var mixed $psr4 */
        $psr4 = require $psr4MapPath;

        if (!is_array($psr4)) {
            return [];
        }

        $normalized = [];

        foreach ($psr4 as $prefix => $directories) {
            if (!is_string($prefix)) {
                continue;
            }

            if (!is_array($directories)) {
                continue;
            }

            $paths = [];

            foreach ($directories as $directory) {
                if (!is_string($directory)) {
                    continue;
                }

                $paths[] = $directory;
            }

            $normalized[$prefix] = $paths;
        }

        return $normalized;
    }

    /**
     * Validate that a discovered symbol looks like a legal PHP class name.
     *
     * Discovery uses this as a defensive filter before attempting to instantiate
     * or load userland classes from Composer metadata.
     */
    private function isValidClassName(string $value): bool
    {
        return preg_match('/^(?:\\\\?[A-Za-z_]\w*)(?:\\\\[A-Za-z_]\w*)*$/', $value) === 1;
    }
}
