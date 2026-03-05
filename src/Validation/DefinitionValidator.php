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
 * Validates registered breadcrumb definitions for missing parents and cycles.
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
    public function __construct(
        private DefinitionRegistry $registry,
    ) {}

    /**
     * Validate all known definitions and collect structural issues.
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
