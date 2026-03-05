<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_key_last;
use function array_map;
use function array_values;
use function count;

/**
 * Immutable ordered breadcrumb collection.
 *
 * The final item is marked as current when the trail is created.
 *
 * @phpstan-type BreadcrumbTrailItemPayload array{
 *     label: string,
 *     url: null|string,
 *     current: bool,
 *     meta: array<string, mixed>,
 *     attributes: array<string, string>
 * }
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 * @implements IteratorAggregate<int, BreadcrumbItem>
 */
final readonly class BreadcrumbTrail implements Countable, IteratorAggregate
{
    /**
     * @param list<BreadcrumbItem> $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param list<BreadcrumbItem> $items
     *
     * Create a trail from item values and normalize the current item.
     */
    public static function fromItems(array $items): self
    {
        $lastIndex = array_key_last($items);

        if ($lastIndex !== null) {
            $items[$lastIndex] = $items[$lastIndex]->withCurrent(true);
        }

        /** @var list<BreadcrumbItem> $items */
        $items = array_values($items);

        return new self($items);
    }

    /**
     * @return list<BreadcrumbItem>
     *
     * Get trail items in display order.
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return list<string>
     *
     * Get breadcrumb labels in display order.
     */
    public function labels(): array
    {
        return array_map(static fn (BreadcrumbItem $item): string => $item->label(), $this->items);
    }

    /**
     * @return list<BreadcrumbTrailItemPayload>
     *
     * Serialize trail items in display order.
     */
    public function toArray(): array
    {
        return array_map(static fn (BreadcrumbItem $item): array => $item->toArray(), $this->items);
    }

    /**
     * Determine whether the trail has no items.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Get the number of trail items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, BreadcrumbItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
