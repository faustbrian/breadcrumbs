<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Discovery;

use Cline\Breadcrumbs\Exceptions\UnableToCreateBreadcrumbCacheDirectoryException;
use Illuminate\Container\Attributes\Singleton;

use function dirname;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function mkdir;
use function throw_if;
use function unlink;
use function var_export;

/**
 * Persists and clears cached breadcrumb definition class names.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class DefinitionCache
{
    /**
     * Load cached definition classes from disk.
     *
     * @return null|list<class-string>
     */
    public function load(string $cachePath): ?array
    {
        if (!is_file($cachePath)) {
            return null;
        }

        $definitions = require $cachePath;

        if (!is_array($definitions)) {
            return null;
        }

        /** @var list<class-string> $definitions */
        return $definitions;
    }

    /**
     * @param list<class-string> $definitions
     *
     * Store resolved definition classes in the configured cache file.
     *
     * @throws UnableToCreateBreadcrumbCacheDirectoryException
     */
    public function write(string $cachePath, array $definitions): void
    {
        $directory = dirname($cachePath);

        throw_if(
            !is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory),
            UnableToCreateBreadcrumbCacheDirectoryException::forPath(),
        );

        $contents = "<?php\n\nreturn ".var_export($definitions, true).";\n";
        file_put_contents($cachePath, $contents);
    }

    /**
     * Remove the cache file when present.
     *
     * @return bool True when the cache file existed and was deleted.
     */
    public function clear(string $cachePath): bool
    {
        if (!is_file($cachePath)) {
            return false;
        }

        return unlink($cachePath);
    }
}
