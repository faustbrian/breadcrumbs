<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs;

use Cline\Breadcrumbs\Console\Commands\CacheBreadcrumbDefinitionsCommand;
use Cline\Breadcrumbs\Console\Commands\ClearBreadcrumbDefinitionsCacheCommand;
use Cline\Breadcrumbs\Console\Commands\ValidateBreadcrumbDefinitionsCommand;
use Cline\Breadcrumbs\View\Components\Trail;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Override;

use function array_keys;
use function config;
use function config_path;
use function glob;
use function is_array;
use function is_file;
use function is_string;
use function sort;

/**
 * Registers container bindings, views, and console commands.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register package configuration and service bindings.
     */
    #[Override()]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/breadcrumbs.php', 'breadcrumbs');
    }

    /**
     * Boot package views, components, publishable assets, and commands.
     */
    public function boot(): void
    {
        $this->loadCallbackDefinitionFiles();

        $this->loadViewComponentsAs('breadcrumbs', [Trail::class]);

        $this->publishes([
            __DIR__.'/../config/breadcrumbs.php' => config_path('breadcrumbs.php'),
        ], 'breadcrumbs-config');

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CacheBreadcrumbDefinitionsCommand::class,
            ClearBreadcrumbDefinitionsCacheCommand::class,
            ValidateBreadcrumbDefinitionsCommand::class,
        ]);
    }

    private function loadCallbackDefinitionFiles(): void
    {
        $patterns = config('breadcrumbs.callbacks.autoload', []);

        if (!is_array($patterns)) {
            return;
        }

        /** @var array<string, true> $files */
        $files = [];

        foreach ($patterns as $pattern) {
            if (!is_string($pattern)) {
                continue;
            }

            if ($pattern === '') {
                continue;
            }

            foreach (glob($pattern) ?: [] as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $files[$file] = true;
            }
        }

        $resolvedFiles = array_keys($files);
        sort($resolvedFiles);

        foreach ($resolvedFiles as $file) {
            require $file;
        }
    }
}
