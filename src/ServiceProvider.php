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
use Cline\Breadcrumbs\Core\BreadcrumbsManager;
use Cline\Breadcrumbs\Core\DefinitionRegistry;
use Cline\Breadcrumbs\Core\RouteContextResolver;
use Cline\Breadcrumbs\Core\TrailResolver;
use Cline\Breadcrumbs\Discovery\DefinitionCache;
use Cline\Breadcrumbs\Discovery\DefinitionClassResolver;
use Cline\Breadcrumbs\Discovery\DefinitionDiscovery;
use Cline\Breadcrumbs\Serialization\SerializerRegistry;
use Cline\Breadcrumbs\Validation\DefinitionValidator;
use Cline\Breadcrumbs\View\Components\Trail;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Override;

use function config_path;

/**
 * Registers container bindings, views, and console commands.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * @return list<class-string>
     */
    #[Override()]
    public function provides(): array
    {
        return [
            BreadcrumbsManager::class,
            DefinitionRegistry::class,
            TrailResolver::class,
            RouteContextResolver::class,
            DefinitionDiscovery::class,
            DefinitionClassResolver::class,
            DefinitionCache::class,
            SerializerRegistry::class,
            DefinitionValidator::class,
        ];
    }

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
}
