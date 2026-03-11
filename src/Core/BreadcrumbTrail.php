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
 * Immutable ordered collection representing a fully built breadcrumb trail.
 *
 * This type is the boundary between the mutable builder phase and all read-only
 * consumers. It preserves display order exactly as produced by resolution and
 * enforces the package invariant that only the terminal item is marked as the
 * current page when the trail is instantiated.
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
     * Create an immutable trail and normalize current-item state.
     *
     * Resolution order is preserved exactly as supplied. If the list is not
     * empty, the last item is cloned with `current=true`; earlier items are
     * left unchanged so callers should treat the provided sequence as a
     * pre-finalized build result rather than already-normalized output.
     *
     * @param list<BreadcrumbItem> $items
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
     * Return all breadcrumb items in display order.
     *
     * The returned list is already finalized, including current-item
     * normalization on the terminal element.
     *
     * @return list<BreadcrumbItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Return just the breadcrumb labels in display order.
     *
     * This is a convenience projection for callers that only need text labels,
     * such as tests or compact serializers.
     *
     * @return list<string>
     */
    public function labels(): array
    {
        return array_map(static fn (BreadcrumbItem $item): string => $item->label(), $this->items);
    }

    /**
     * Serialize the trail into the package's canonical array payload.
     *
     * Each item is converted using its own stable serialization contract, so
     * the resulting list is suitable for JSON responses, view data, and custom
     * serializer implementations.
     *
     * @return list<BreadcrumbTrailItemPayload>
     */
    public function toArray(): array
    {
        return array_map(static fn (BreadcrumbItem $item): array => $item->toArray(), $this->items);
    }

    /**
     * Determine whether resolution produced no breadcrumb items.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Return the number of items in the finalized trail.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Iterate over trail items in display order.
     *
     * Iteration is backed by the normalized immutable list stored by the trail.
     *
     * @return Traversable<int, BreadcrumbItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
