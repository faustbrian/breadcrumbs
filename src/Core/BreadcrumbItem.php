<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use Cline\Breadcrumbs\Contracts\ArrayableBreadcrumbItem;

/**
 * Immutable value object for one rendered breadcrumb node.
 *
 * A breadcrumb item is the stable handoff format between the mutable build
 * phase and downstream serializers or views. It stores the user-facing label,
 * optional destination URL, current-page marker, and auxiliary metadata needed
 * by custom renderers without exposing any mutation APIs.
 *
 * The item does not infer whether it is current on its own. That responsibility
 * belongs to `BreadcrumbTrail`, which normalizes the final element after the
 * full ordered list has been assembled.
 *
 * @phpstan-type BreadcrumbItemPayload array{
 *     label: string,
 *     url: null|string,
 *     current: bool,
 *     meta: array<string, mixed>,
 *     attributes: array<string, string>
 * }
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 * @implements ArrayableBreadcrumbItem<BreadcrumbItemPayload>
 */
final readonly class BreadcrumbItem implements ArrayableBreadcrumbItem
{
    /**
     * Create a new immutable breadcrumb item.
     *
     * @param array<string, mixed>  $meta
     * @param array<string, string> $attributes
     */
    public function __construct(
        private string $label,
        private ?string $url = null,
        private bool $current = false,
        private array $meta = [],
        private array $attributes = [],
    ) {}

    /**
     * Return the human-readable label that should be rendered for this item.
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * Return the navigation target for this item, if it is linkable.
     *
     * `null` indicates a non-link breadcrumb, which is common for the current
     * page or for display-only intermediate nodes.
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Determine whether this item represents the terminal current page.
     */
    public function isCurrent(): bool
    {
        return $this->current;
    }

    /**
     * Return renderer-specific metadata attached during trail building.
     *
     * The package does not interpret these values, but they are preserved in
     * array serialization so higher-level integrations can.
     *
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Return HTML or transport attributes associated with this item.
     *
     * Values are constrained to strings so the payload can be forwarded to
     * templating layers without additional normalization.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Clone the item with a different current-page marker.
     *
     * This is used by `BreadcrumbTrail` during finalization so current-state
     * normalization does not require mutating items gathered by the builder.
     */
    public function withCurrent(bool $current = true): self
    {
        return new self($this->label, $this->url, $current, $this->meta, $this->attributes);
    }

    /**
     * Serialize the item into the package's canonical item payload shape.
     *
     * The array keys are intentionally stable because `BreadcrumbTrail` and
     * serializer implementations may rely on them as an integration contract.
     *
     * @return BreadcrumbItemPayload
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'url' => $this->url,
            'current' => $this->current,
            'meta' => $this->meta,
            'attributes' => $this->attributes,
        ];
    }
}
