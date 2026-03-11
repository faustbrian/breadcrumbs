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
 * Primary application-facing entry point for breadcrumb registration and output.
 *
 * This manager sits at the outer edge of the package lifecycle. Callers use it
 * to register callback-backed definitions, resolve a trail for the current or a
 * named route context, and project the resulting immutable trail into views,
 * arrays, JSON-LD, or arbitrary serializer formats.
 *
 * Resolution is delegated rather than implemented here: route inference happens
 * through {@see RouteContextResolver}, trail construction through
 * {@see TrailResolver}, and output formatting through the serializer registry.
 * That keeps this class responsible for orchestration, format-specific guard
 * rails, and enforcing the package's configured rendering defaults.
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
     * Register a callback-backed definition under the provided breadcrumb name.
     *
     * This augments the runtime definition set managed by the route registrar.
     * Registrations take effect for subsequent resolutions in the current
     * process and follow the registrar's own replacement and grouping rules.
     */
    public function for(string $name, Closure $callback): void
    {
        $this->registrar->for($name, $callback);
    }

    /**
     * Register a callback-backed definition using route-style naming ergonomics.
     *
     * This is a semantic alias of {@see self::for()} and exists so consuming
     * code can mirror Laravel route registration vocabulary.
     */
    public function as(string $name, Closure $callback): void
    {
        $this->registrar->as($name, $callback);
    }

    /**
     * Group multiple callback registrations under shared registrar attributes.
     *
     * The attributes are forwarded unchanged to the route-side registrar, which
     * means prefixing, middleware-style metadata, or other registrar-supported
     * grouping behavior is resolved outside this manager.
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
     * Resolve the effective trail for either an explicit breadcrumb name or the
     * current router state.
     *
     * When `$name` is omitted, the current route name and route parameters are
     * captured first and used as the breadcrumb context. When `$name` is
     * provided, that explicit context takes precedence and the caller-supplied
     * `$params` become the full parameter set seen by the matching definition.
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
     * Render the resolved trail into a Laravel view instance.
     *
     * View selection is deterministic: an explicit `$view` overrides the
     * package default, and an empty resolved view name is treated as a
     * configuration failure instead of falling back silently. The resolved trail
     * is exposed to the view as the `trail` variable.
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
     * Serialize the resolved trail using the `jsonld` serializer and guarantee
     * that the payload shape remains array-based.
     *
     * The method fails fast when a custom serializer returns any non-array
     * payload so callers can rely on JSON-LD data being structurally suitable
     * for schema output without performing additional type checks.
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
     * Serialize the trail using the canonical `trail` serializer.
     *
     * This is the package's normalized machine-readable representation: each
     * breadcrumb item is reduced to scalar-friendly payload data while
     * preserving ordering, current-item state, metadata, and HTML attributes.
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
     *
     * Serialize the resolved trail through the named serializer.
     *
     * Serializer lookup is delegated to the registry, so unknown formats or
     * format-specific failures surface from that layer. No response wrapping or
     * payload validation happens here beyond the stronger guarantees offered by
     * the format-specific helpers above.
     */
    public function serialize(?string $name = null, array $params = [], string $format = 'trail'): mixed
    {
        return $this->serializers->resolve($format)->serialize($this->trail($name, $params));
    }

    /**
     * @param array<string, mixed> $params
     *
     * Create a JSON response from the serialized trail payload.
     *
     * This is a thin transport adapter over {@see self::serialize()}; any
     * serializer-side exception or resolution failure propagates before the
     * response is instantiated.
     */
    public function toResponse(?string $name = null, array $params = [], string $format = 'trail'): JsonResponse
    {
        return new JsonResponse($this->serialize($name, $params, $format));
    }
}
