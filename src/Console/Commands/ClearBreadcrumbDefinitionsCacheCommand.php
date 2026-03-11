<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Console\Commands;

use Cline\Breadcrumbs\Discovery\DefinitionCache;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Config;
use Override;

/**
 * Remove the persisted definition cache used by runtime resolution.
 *
 * This command is the operational counterpart to `breadcrumbs:cache`. It
 * deletes the configured cache file so the next resolution pass must rebuild
 * the definition list from configuration and optional discovery sources.
 *
 * An empty cache path is treated as a configuration error because clearing an
 * unknown location would be unsafe. A missing cache file is not considered a
 * failure and is reported as a no-op so cache clears remain idempotent.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ClearBreadcrumbDefinitionsCacheCommand extends Command
{
    #[Override()]
    protected $signature = 'breadcrumbs:clear';

    #[Override()]
    protected $description = 'Clear cached breadcrumb definition classes';

    public function __construct(
        private readonly DefinitionCache $cache,
        #[Config('breadcrumbs.cache.path')]
        private readonly string $cachePath,
    ) {
        parent::__construct();
    }

    /**
     * Delete the configured cache file when it exists.
     *
     * Returns `FAILURE` only when the cache path configuration is invalid. When
     * no file exists, the command still succeeds after reporting that nothing
     * needed to be removed.
     *
     * @return self::FAILURE|self::SUCCESS
     */
    public function handle(): int
    {
        if ($this->cachePath === '') {
            $this->error('Invalid breadcrumbs.cache.path configuration.');

            return self::FAILURE;
        }

        $deleted = $this->cache->clear($this->cachePath);

        $this->info($deleted ? 'Breadcrumb cache cleared.' : 'No breadcrumb cache file found.');

        return self::SUCCESS;
    }
}
