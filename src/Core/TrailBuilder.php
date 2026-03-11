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
 * Mutable trail assembly object passed into breadcrumb definitions.
 *
 * Definitions use this builder during the `build` phase to declare parent
 * relationships and append concrete breadcrumb items. The builder is short
 * lived: a new instance is created for each definition resolution, populated in
 * userland code, then collapsed into immutable trail items by the resolver.
 *
 * Parent resolution is delegated through the injected callback rather than
 * performed inline, which keeps the builder focused on definition authoring
 * ergonomics while the resolver remains responsible for recursion, cycle
 * detection, and parameter propagation.
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
     * The callback bridges builder calls back into the recursive resolver.
     */
    public function __construct(
        private readonly mixed $resolveParent,
    ) {}

    /**
     * Request that another named breadcrumb trail be resolved first.
     *
     * Parents are always expanded before the current definition's own items,
     * preserving breadcrumb ancestry order. Passing `null` for `$params`
     * delegates parameter reuse decisions to the resolver callback.
     *
     * @param null|array<string, mixed> $params
     */
    public function parent(string $name, ?array $params = null): self
    {
        ($this->resolveParent)($name, $params);

        return $this;
    }

    /**
     * Append a concrete breadcrumb item for the current definition.
     *
     * Items are recorded in insertion order and are not marked current at this
     * stage; current-item semantics are applied later when the immutable trail
     * is finalized. Metadata and HTML attributes are carried through untouched
     * for serializers and view rendering.
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
     * Translate a label through Laravel's translator before appending it.
     *
     * Translation happens eagerly during definition execution, so the resolved
     * string stored in the trail reflects the locale and replacement data
     * available at build time.
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
     * Return the items collected for this definition in build order.
     *
     * No defensive copy or reindexing is performed beyond normal array return
     * semantics, so the order exactly matches the calls made by the definition.
     */
    public function items(): array
    {
        return $this->items;
    }
}
