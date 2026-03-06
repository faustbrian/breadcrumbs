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
 * Route-style fluent builder exposed to callback-registered breadcrumbs.
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
     * @param null|array<string, mixed> $params
     */
    public function parent(string $name, ?array $params = null): self
    {
        $this->trail->parent($name, $this->normalizeNamedParams($params ?? $this->context->params()));

        return $this;
    }

    public function push(string $label, ?string $url = null): self
    {
        $this->trail->push($label, $url);

        return $this;
    }

    /**
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
