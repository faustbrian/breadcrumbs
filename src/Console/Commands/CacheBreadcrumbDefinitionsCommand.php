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
 * Warm the on-disk definition cache used by breadcrumb bootstrapping.
 *
 * This command runs the same definition resolution pipeline the package uses at
 * runtime, then persists the resulting class list to the configured cache file.
 * It exists for deployments that want deterministic startup cost and do not
 * want to repeat filesystem discovery on every request.
 *
 * The command fails fast when the configured cache path is empty because there
 * is no safe fallback location to write to. Resolver and filesystem failures
 * are allowed to bubble so the operator sees the underlying problem instead of
 * a partial success message.
 *
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
     * Resolve the current definition set and write it to the cache file.
     *
     * The command reports the number of resolved classes that were persisted.
     * A `FAILURE` exit code is returned only for an invalid configured cache
     * path; write and resolution exceptions continue to surface normally.
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
