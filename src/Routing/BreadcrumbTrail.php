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
        $this->trail->parent($name, $params ?? $this->context->params());

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
}
