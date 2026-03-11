<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Routing;

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;
use Illuminate\Contracts\Routing\UrlRoutable;

use function is_array;

/**
 * Route-oriented facade over the lower-level trail builder used inside callbacks.
 *
 * Callback breadcrumb definitions receive this type by default as their first
 * argument so registration code can read like Laravel's route breadcrumb APIs
 * while the package still builds against the immutable core trail builder. It
 * is responsible for normalizing parent parameters, especially `UrlRoutable`
 * instances nested inside arrays, before delegating to the underlying builder.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class BreadcrumbTrail
{
    public function __construct(
        private TrailBuilder $trail,
        private BreadcrumbContext $context,
    ) {}

    /**
     * Append another breadcrumb definition as the parent of the current trail.
     *
     * When `$params` is omitted the current breadcrumb context parameters are
     * forwarded, allowing child definitions to inherit route-model bindings and
     * other resolved inputs automatically. Any nested `UrlRoutable` values are
     * converted to their route keys before delegation so parent resolution stays
     * deterministic and serializer-safe.
     *
     * @param null|array<string, mixed> $params
     */
    public function parent(string $name, ?array $params = null): self
    {
        $this->trail->parent($name, $this->normalizeNamedParams($params ?? $this->context->params()));

        return $this;
    }

    /**
     * Push a concrete breadcrumb item onto the trail in build order.
     *
     * Items are appended immediately to the shared underlying builder, so call
     * order becomes the rendered and serialized order seen by consumers.
     */
    public function push(string $label, ?string $url = null): self
    {
        $this->trail->push($label, $url);

        return $this;
    }

    /**
     * Push a translated breadcrumb item with optional metadata and HTML attributes.
     *
     * Translation is deferred to the underlying builder so locale selection,
     * placeholder replacement, and payload shaping remain consistent with other
     * item creation paths in the package.
     *
     * @param array<string, mixed>  $replace
     * @param array<string, mixed>  $meta
     * @param array<string, string> $attributes
     */
    public function pushTranslated(
        string $translationKey,
        array $replace = [],
        ?string $locale = null,
        ?string $url = null,
        array $meta = [],
        array $attributes = [],
    ): self {
        $this->trail->pushTranslated($translationKey, $replace, $locale, $url, $meta, $attributes);

        return $this;
    }

    /**
     * Normalize named parent parameters before passing them to the builder.
     *
     * This preserves the original array shape while recursively converting route
     * models into stable route keys so named parameter lookups still match the
     * target breadcrumb definition.
     *
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function normalizeNamedParams(array $params): array
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    /**
     * Normalize a nested parameter array using the same rules as top-level values.
     *
     * Numeric keys are preserved because some breadcrumb parameters represent
     * ordered route segments rather than named placeholders.
     *
     * @param  array<array-key, mixed> $params
     * @return array<array-key, mixed>
     */
    private function normalizeParams(array $params): array
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    /**
     * Convert route-model objects into route keys while preserving scalar values.
     *
     * Arrays are normalized recursively. Unsupported object detection is handled
     * elsewhere in the pipeline, so this method only performs the safe
     * conversions needed for route-style callback ergonomics.
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof UrlRoutable) {
            return $value->getRouteKey();
        }

        if (is_array($value)) {
            return $this->normalizeParams($value);
        }

        return $value;
    }
}
