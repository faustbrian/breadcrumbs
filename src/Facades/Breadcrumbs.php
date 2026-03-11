<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Facades;

use Cline\Breadcrumbs\Core\BreadcrumbsManager;
use Cline\Breadcrumbs\Core\BreadcrumbTrail;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Facade entry point for the package's breadcrumb registration and rendering API.
 *
 * This facade fronts the singleton breadcrumbs manager that owns definition
 * registration, trail resolution, serialization, and view rendering. It is the
 * public static surface consumers typically interact with from service
 * providers, route files, controllers, and Blade directives, while the actual
 * lifecycle and state remain container-managed.
 *
 * @phpstan-type BreadcrumbTrailItemPayload array{
 *     label: string,
 *     url: null|string,
 *     current: bool,
 *     meta: array<string, mixed>,
 *     attributes: array<string, string>
 * }
 * @phpstan-type BreadcrumbJsonLdPayload array{
 *     '@context': string,
 *     '@type': string,
 *     itemListElement: list<array{
 *         '@type': string,
 *         position: int,
 *         name: string,
 *         item?: string
 *     }>
 * }
 *
 * @method static void                             as(string $name, Closure $callback)
 * @method static list<BreadcrumbTrailItemPayload> asArray(?string $name = null, array<string, mixed> $params = [])
 * @method static void                             for(string $name, Closure $callback)
 * @method static mixed                            group(array<string, mixed>|string $attributes, Closure $callback)
 * @method static BreadcrumbJsonLdPayload          jsonLd(?string $name = null, array<string, mixed> $params = [])
 * @method static View                             render(?string $name = null, array<string, mixed> $params = [], ?string $view = null)
 * @method static JsonResponse                     toResponse(?string $name = null, array<string, mixed> $params = [], string $format = 'trail')
 * @method static BreadcrumbTrail                  trail(?string $name = null, array<string, mixed> $params = [])
 * @author Brian Faust <brian@cline.sh>
 */
final class Breadcrumbs extends Facade
{
    /**
     * Get the container binding used to resolve the underlying manager.
     *
     * The facade always resolves to the same manager singleton, ensuring that
     * callback registrations and serializer lookups share one registry for the
     * duration of the Laravel application lifecycle.
     */
    protected static function getFacadeAccessor(): string
    {
        return BreadcrumbsManager::class;
    }
}
