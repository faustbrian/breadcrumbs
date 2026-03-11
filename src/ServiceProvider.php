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
 * Laravel service provider that boots the breadcrumbs package into an application.
 *
 * The provider participates in two lifecycle phases:
 * - `register()` merges package defaults so later consumers read a complete config tree
 * - `boot()` loads callback definition files, registers view components,
 *   exposes publishable config, and conditionally registers console commands
 *
 * Callback definition loading happens during boot so application config is
 * already available and definition files can resolve package services.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServiceProvider extends BaseServiceProvider
{
    /**
     * Merge the package configuration into the application's config repository.
     *
     * This runs early in the provider lifecycle so later boot logic and any
     * downstream services see user overrides layered on top of package defaults.
     */
    #[Override()]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/breadcrumbs.php', 'breadcrumbs');
    }

    /**
     * Boot runtime integrations after the container has been registered.
     *
     * Definition files are loaded first so any later view rendering or response
     * generation sees the complete callback registry. Console-only commands are
     * registered lazily to avoid unnecessary runtime noise for web requests.
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

    /**
     * Require configured callback definition files in deterministic order.
     *
     * The `breadcrumbs.callbacks.autoload` config value may contain glob
     * patterns. Non-string entries, empty patterns, and non-files are ignored.
     * Duplicate matches are de-duplicated, then the final file list is sorted
     * before requiring each file so registration order remains stable across
     * filesystems and repeated boots.
     */
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
