<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use Cline\Breadcrumbs\Exceptions\InvalidBreadcrumbSerializerPayloadException;
use Cline\Breadcrumbs\Exceptions\ViewNotConfiguredException;
use Cline\Breadcrumbs\Routing\DefinitionRegistrar;
use Cline\Breadcrumbs\Serialization\SerializerRegistry;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

use function is_array;
use function throw_if;

/**
 * Primary API for resolving, rendering, and serializing breadcrumb trails.
 *
 * @phpstan-type BreadcrumbTrailItemPayload array{
 *     label: string,
 *     url: null|string,
 *     current: bool,
 *     meta: array<string, mixed>,
 *     attributes: array<string, string>
 * }
 * @phpstan-type BreadcrumbJsonLdListItem array{
 *     '@type': string,
 *     position: int,
 *     name: string,
 *     item?: string
 * }
 * @phpstan-type BreadcrumbJsonLdPayload array{
 *     '@context': string,
 *     '@type': string,
 *     itemListElement: list<BreadcrumbJsonLdListItem>
 * }
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
#[Singleton()]
final readonly class BreadcrumbsManager
{
    public function __construct(
        private TrailResolver $resolver,
        private RouteContextResolver $routeContextResolver,
        private ViewFactory $viewFactory,
        private SerializerRegistry $serializers,
        private DefinitionRegistrar $registrar,
        #[Config('breadcrumbs.view')]
        private string $defaultView,
    ) {}

    /**
     * Register a callback-based breadcrumb definition.
     */
    public function for(string $name, Closure $callback): void
    {
        $this->registrar->for($name, $callback);
    }

    /**
     * Alias of `for` for route-like ergonomics.
     */
    public function as(string $name, Closure $callback): void
    {
        $this->registrar->as($name, $callback);
    }

    /**
     * Group callback-based breadcrumb registrations.
     *
     * @param array<string, mixed>|string $attributes
     */
    public function group(array|string $attributes, Closure $callback): mixed
    {
        return $this->registrar->group($attributes, $callback);
    }

    /**
     * @param array<string, mixed> $params
     *
     * Resolve a breadcrumb trail for a named or current route context.
     */
    public function trail(?string $name = null, array $params = []): BreadcrumbTrail
    {
        if ($name === null) {
            return $this->resolver->resolve($this->routeContextResolver->resolve());
        }

        return $this->resolver->resolve(
            new BreadcrumbContext($name, $params),
        );
    }

    /**
     * @param array<string, mixed> $params
     *
     * Render the breadcrumb trail with the configured or provided view.
     *
     * @throws ViewNotConfiguredException
     */
    public function render(?string $name = null, array $params = [], ?string $view = null): View
    {
        $viewName = $view ?? $this->defaultView;

        throw_if($viewName === '', ViewNotConfiguredException::forBreadcrumbsView());

        return $this->viewFactory->make($viewName, ['trail' => $this->trail($name, $params)]);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return BreadcrumbJsonLdPayload
     */
    public function jsonLd(?string $name = null, array $params = []): array
    {
        $jsonLd = $this->serialize($name, $params, 'jsonld');
        throw_if(
            !is_array($jsonLd),
            InvalidBreadcrumbSerializerPayloadException::arrayRequiredForFormat('jsonld'),
        );

        /** @var BreadcrumbJsonLdPayload $jsonLd */
        return $jsonLd;
    }

    /**
     * @param  array<string, mixed>             $params
     * @return list<BreadcrumbTrailItemPayload>
     *
     * Return the breadcrumb trail as a serializable list of item payloads.
     */
    public function asArray(?string $name = null, array $params = []): array
    {
        $trail = $this->serialize($name, $params, 'trail');
        throw_if(
            !is_array($trail),
            InvalidBreadcrumbSerializerPayloadException::arrayRequiredForFormat('trail'),
        );

        /** @var list<BreadcrumbTrailItemPayload> $trail */
        return $trail;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function serialize(?string $name = null, array $params = [], string $format = 'trail'): mixed
    {
        return $this->serializers->resolve($format)->serialize($this->trail($name, $params));
    }

    /**
     * @param array<string, mixed> $params
     */
    public function toResponse(?string $name = null, array $params = [], string $format = 'trail'): JsonResponse
    {
        return new JsonResponse($this->serialize($name, $params, $format));
    }
}
