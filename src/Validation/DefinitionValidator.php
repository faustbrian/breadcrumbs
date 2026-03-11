<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Validation;

use Cline\Breadcrumbs\Contracts\ParentAwareBreadcrumbDefinition;
use Cline\Breadcrumbs\Core\DefinitionRegistry;
use Illuminate\Container\Attributes\Singleton;

use function array_keys;
use function array_search;
use function array_slice;
use function array_unique;
use function array_values;
use function implode;
use function in_array;

/**
 * Verifies that the registered breadcrumb definitions form a resolvable parent
 * graph.
 *
 * This validator runs against the in-memory {@see DefinitionRegistry} after
 * definitions have been registered but before consumers rely on parent chains
 * to build breadcrumb trails. Its job is purely structural: it checks that
 * every declared parent exists and that traversing parent links cannot loop
 * forever.
 *
 * The validator does not mutate the registry or attempt recovery. Instead it
 * produces a {@see DefinitionValidationResult} describing every missing edge
 * and every unique cycle discovered during graph traversal so package boot or
 * tests can decide how hard to fail.
 *
 * @phpstan-type DefinitionGraph array<string, list<string>>
 * @phpstan-type BreadcrumbCycle list<string>
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class DefinitionValidator
{
    /**
     * @param DefinitionRegistry $registry Registry snapshot to validate.
     */
    public function __construct(
        private DefinitionRegistry $registry,
    ) {}

    /**
     * Validate all registered definitions and collect structural issues.
     *
     * Validation happens in two passes:
     * 1. Build an adjacency list of parent relationships for every registered
     * definition while recording any parent names missing from the registry.
     * 2. Depth-first walk the resulting graph from every node to detect cycles.
     *
     * The returned result is exhaustive for the current registry snapshot. This
     * method has no side effects beyond reading definitions, so it can be called
     * during boot, tests, or diagnostics without altering runtime state.
     */
    public function validate(): DefinitionValidationResult
    {
        $definitions = $this->registry->all();
        $graph = [];
        $missing = [];

        foreach ($definitions as $name => $definition) {
            $graph[$name] = [];

            if (!$definition instanceof ParentAwareBreadcrumbDefinition) {
                continue;
            }

            foreach ($definition->parents() as $parent) {
                $graph[$name][] = $parent;

                if ($this->registry->has($parent)) {
                    continue;
                }

                $missing[] = $name.' -> '.$parent;
            }
        }

        $cycles = [];

        foreach (array_keys($graph) as $node) {
            $this->walk($node, $graph, [], $cycles);
        }

        return new DefinitionValidationResult(array_values(array_unique($missing)), array_values($cycles));
    }

    /**
     * Walk the parent graph from a node and record any cycles encountered.
     *
     * The traversal follows parent order exactly as definitions declare it so
     * cycle reports mirror the resolution order used by the package. Missing
     * nodes terminate the walk quietly because they are reported separately as
     * missing-parent edges during graph construction.
     *
     * @param DefinitionGraph                $graph
     * @param list<string>                   $path
     * @param array<string, BreadcrumbCycle> $cycles
     */
    private function walk(string $node, array $graph, array $path, array &$cycles): void
    {
        if (!isset($graph[$node])) {
            return;
        }

        if (in_array($node, $path, true)) {
            $start = array_search($node, $path, true);

            if ($start === false) {
                return;
            }

            $cycle = [...array_slice($path, $start), $node];
            $key = implode(' -> ', $cycle);
            $cycles[$key] = $cycle;

            return;
        }

        $nextPath = [...$path, $node];

        foreach ($graph[$node] as $parent) {
            $this->walk($parent, $graph, $nextPath, $cycles);
        }
    }
}
