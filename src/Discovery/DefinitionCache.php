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
 * File-backed cache for resolved breadcrumb definition class names.
 *
 * This cache sits between expensive discovery and registry construction. It
 * stores the final ordered list of definition classes as executable PHP so a
 * warm cache can be loaded with a simple `require`, avoiding repeated classmap
 * scanning and PSR-4 traversal during application boot.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class DefinitionCache
{
    /**
     * Load a previously written definition list from the cache file.
     *
     * Invalid cache contents are treated the same as a cache miss. The caller
     * receives `null` when the file is absent or when it does not evaluate to
     * an array payload, allowing discovery to proceed without partial state.
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
     * Persist the resolved class list as a PHP return file.
     *
     * The parent directory is created on demand. Failure to create that
     * directory is escalated as a package exception because cache warm-up cannot
     * continue safely without a writable destination.
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
     * A missing file is not considered an error and returns `false`, which lets
     * callers distinguish "nothing to clear" from a successful deletion.
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
