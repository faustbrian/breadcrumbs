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
 * Discovers breadcrumb definition classes from classmaps and PSR-4 mappings.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class DefinitionDiscovery
{
    /**
     * @param list<string> $classmapPaths
     */
    public function __construct(
        #[Config('breadcrumbs.discovery.classmap_paths', [])]
        private array $classmapPaths,
    ) {}

    /**
     * @param  list<string>       $paths
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
     * @param  list<string> $paths
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
     * @param  list<string>                $paths
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
     * @param  list<string>                $paths
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
     * @param  array<string, list<string>> $psr4
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

    private function isValidClassName(string $value): bool
    {
        return preg_match('/^(?:\\\\?[A-Za-z_]\w*)(?:\\\\[A-Za-z_]\w*)*$/', $value) === 1;
    }
}
