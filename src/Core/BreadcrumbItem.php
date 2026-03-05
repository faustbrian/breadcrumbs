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
 * Immutable value object representing a breadcrumb item.
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
     * Get the breadcrumb label.
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * Get the breadcrumb URL.
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Determine whether this item is the current breadcrumb.
     */
    public function isCurrent(): bool
    {
        return $this->current;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Return a copy with the current-item state updated.
     */
    public function withCurrent(bool $current = true): self
    {
        return new self($this->label, $this->url, $current, $this->meta, $this->attributes);
    }

    /**
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
