<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use Cline\Breadcrumbs\Exceptions\MissingCurrentRouteNameException;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Routing\Router;

use function throw_if;

/**
 * Resolves the current route into a breadcrumb context.
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
     * Resolve current route name and parameters for breadcrumb processing.
     *
     * @throws MissingCurrentRouteNameException
     * @return BreadcrumbContext                Context using current route name and parameters.
     */
    public function resolve(): BreadcrumbContext
    {
        $route = $this->router->current();

        throw_if(
            $route === null || $route->getName() === null,
            MissingCurrentRouteNameException::forCurrentRoute(),
        );

        /** @var array<string, mixed> $params */
        $params = $route->parameters();

        return new BreadcrumbContext($route->getName(), $params);
    }
}
