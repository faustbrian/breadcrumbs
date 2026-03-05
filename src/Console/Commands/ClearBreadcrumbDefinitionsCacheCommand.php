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
 * Clears cached breadcrumb definition classes from disk.
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
     * Execute the command.
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
