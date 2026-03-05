<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use function __;

/**
 * Mutable builder used by definitions to append breadcrumb items.
 *
 * @phpstan-type ParentResolver callable(string, null|array<string, mixed>): void
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TrailBuilder
{
    /** @var list<BreadcrumbItem> */
    private array $items = [];

    /**
     * @param ParentResolver $resolveParent
     *
     * Callback is invoked when a definition links to a parent breadcrumb.
     */
    public function __construct(
        private readonly mixed $resolveParent,
    ) {}

    /**
     * Resolve and append a named parent breadcrumb trail.
     *
     * @param null|array<string, mixed> $params
     */
    public function parent(string $name, ?array $params = null): self
    {
        ($this->resolveParent)($name, $params);

        return $this;
    }

    /**
     * Append a breadcrumb item to the trail.
     *
     * @param array<string, mixed>  $meta
     * @param array<string, string> $attributes
     */
    public function push(string $label, ?string $url = null, array $meta = [], array $attributes = []): self
    {
        $this->items[] = new BreadcrumbItem($label, $url, false, $meta, $attributes);

        return $this;
    }

    /**
     * Translate and append a breadcrumb item.
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
        /** @var string $translated */
        $translated = __($translationKey, $replace, $locale);

        return $this->push($translated, $url, $meta, $attributes);
    }

    /**
     * @return list<BreadcrumbItem>
     *
     * Get collected breadcrumb items in insertion order.
     */
    public function items(): array
    {
        return $this->items;
    }
}
