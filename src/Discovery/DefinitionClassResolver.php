<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Discovery;

use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;

use function array_merge;
use function array_unique;
use function array_values;

/**
 * Resolves the authoritative definition class list used to build the registry.
 *
 * This class decides where definition classes come from and in what order they
 * should be considered. It merges explicit configuration with optional runtime
 * discovery, while giving an enabled cache the first chance to short-circuit
 * the whole process.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
#[Singleton()]
final readonly class DefinitionClassResolver
{
    /**
     * @param list<class-string> $definitions
     * @param list<string>       $discoveryPaths
     */
    public function __construct(
        private DefinitionDiscovery $discovery,
        private DefinitionCache $cache,
        #[Config('breadcrumbs.cache.enabled', true)]
        private bool $cacheEnabled,
        #[Config('breadcrumbs.cache.path')]
        private string $cachePath,
        /** @var list<class-string> */
        #[Config('breadcrumbs.definitions', [])]
        private array $definitions,
        #[Config('breadcrumbs.discovery.enabled', false)]
        private bool $discoveryEnabled,
        /** @var list<string> */
        #[Config('breadcrumbs.discovery.paths', [])]
        private array $discoveryPaths,
    ) {}

    /**
     * Resolve definition classes from cache, config, and optional discovery.
     *
     * Resolution order is:
     * 1. Return the cached class list when caching is enabled and yields data.
     * 2. Start from explicitly configured definitions.
     * 3. Optionally append discovered classes from non-empty discovery paths.
     * 4. De-duplicate while preserving first-seen order.
     *
     * The method does not write cache entries itself; it only consumes them.
     *
     * @return list<class-string>
     */
    public function resolve(): array
    {
        if ($this->cacheEnabled && $this->cachePath !== '') {
            $cached = $this->cache->load($this->cachePath);

            if ($cached !== null) {
                return $cached;
            }
        }

        $definitions = $this->definitions;

        if ($this->discoveryEnabled) {
            $resolvedPaths = [];

            foreach ($this->discoveryPaths as $path) {
                if ($path === '') {
                    continue;
                }

                $resolvedPaths[] = $path;
            }

            $definitions = array_values(array_unique(array_merge($definitions, $this->discovery->discover($resolvedPaths))));
        }

        return $definitions;
    }
}
