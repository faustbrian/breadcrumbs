<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Validation;

/**
 * Immutable report describing structural issues in the registered breadcrumb
 * graph.
 *
 * This value object is the handoff between {@see DefinitionValidator} and any
 * caller that needs to inspect registry health, surface diagnostics, or decide
 * whether bootstrapping can continue. It carries only normalized issue data and
 * never mutates after construction, which makes it safe to cache, log, or
 * reuse across validation consumers.
 *
 * Missing parents are represented as directed `child -> parent` edges so the
 * failing dependency order is explicit. Cycles are stored as ordered node paths
 * that repeat the starting node at the end, allowing callers to display the
 * exact loop that prevents a deterministic breadcrumb hierarchy.
 *
 * @phpstan-type MissingParentEdge string
 * @phpstan-type BreadcrumbCycle list<string>
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class DefinitionValidationResult
{
    /**
     * Create a new immutable validation snapshot.
     *
     * The validator is expected to deduplicate and normalize these lists before
     * instantiation. This type preserves that ordering verbatim so higher layers
     * can present issues consistently across runs.
     *
     * @param list<MissingParentEdge> $missingParents
     * @param list<BreadcrumbCycle>   $cycles
     */
    public function __construct(
        private array $missingParents,
        private array $cycles,
    ) {}

    /**
     * Return every parent reference that points to an unregistered definition.
     *
     * Each entry is formatted as `child -> parent`, matching the dependency edge
     * discovered during validation. An empty list means every declared parent
     * can be resolved from the registry.
     *
     * @return list<MissingParentEdge>
     */
    public function missingParents(): array
    {
        return $this->missingParents;
    }

    /**
     * Return every cycle detected while walking the breadcrumb dependency graph.
     *
     * Each cycle is emitted as an ordered path whose final element repeats the
     * starting node, making the loop explicit for diagnostics and tests.
     *
     * @return list<BreadcrumbCycle>
     */
    public function cycles(): array
    {
        return $this->cycles;
    }

    /**
     * Determine whether validation completed without structural errors.
     *
     * A result is considered valid only when both missing-parent edges and
     * cycle detections are absent. Callers can use this as the single gate for
     * deciding whether the breadcrumb graph is safe to consume.
     */
    public function isValid(): bool
    {
        return $this->missingParents === [] && $this->cycles === [];
    }
}
