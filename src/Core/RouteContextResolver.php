<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use Cline\Breadcrumbs\Exceptions\MissingCurrentRouteNameException;
use Cline\Breadcrumbs\Support\BreadcrumbParamResolver;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Routing\Router;

use function throw_if;

/**
 * Converts the active Laravel route into the package's breadcrumb context.
 *
 * This resolver sits at the boundary between the router and breadcrumb
 * resolution. It snapshots the current route name together with normalized
 * route parameters so downstream trail resolution can operate on a stable
 * {@see BreadcrumbContext} value object instead of depending directly on the
 * router at every step.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class RouteContextResolver
{
    public function __construct(
        private Router $router,
    ) {}

    /**
     * Resolve the current route into a breadcrumb context.
     *
     * The route name is mandatory because the package uses it as the lookup key
     * for definitions. If the router has no current route or the route is
     * unnamed, resolution stops immediately instead of guessing a fallback.
     * Parameters are normalized through {@see BreadcrumbParamResolver} so
     * implicit model bindings and route values reach definitions in a consistent
     * shape.
     *
     * @throws MissingCurrentRouteNameException
     */
    public function resolve(): BreadcrumbContext
    {
        $route = $this->router->current();

        throw_if(
            $route === null || $route->getName() === null,
            MissingCurrentRouteNameException::forCurrentRoute(),
        );

        $params = BreadcrumbParamResolver::fromRoute($route);

        return new BreadcrumbContext($route->getName(), $params);
    }
}
