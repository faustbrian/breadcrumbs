<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Console\Commands;

use Cline\Breadcrumbs\Discovery\DefinitionCache;
use Cline\Breadcrumbs\Discovery\DefinitionClassResolver;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Config;
use Override;

use function count;

/**
 * Resolves and caches breadcrumb definition classes.
 * @author Brian Faust <brian@cline.sh>
 */
final class CacheBreadcrumbDefinitionsCommand extends Command
{
    #[Override()]
    protected $signature = 'breadcrumbs:cache';

    #[Override()]
    protected $description = 'Cache resolved breadcrumb definition classes';

    public function __construct(
        private readonly DefinitionClassResolver $resolver,
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

        $definitions = $this->resolver->resolve();

        $this->cache->write($this->cachePath, $definitions);

        $this->info('Breadcrumb definitions cached: '.count($definitions));

        return self::SUCCESS;
    }
}
