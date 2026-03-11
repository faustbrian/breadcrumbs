<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use Cline\Breadcrumbs\Exceptions\BreadcrumbCycleDetectedException;
use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbDefinitionException;
use Illuminate\Container\Attributes\Singleton;

use function array_merge;
use function in_array;
use function throw_if;

/**
 * Recursively expands breadcrumb definitions into a complete ordered trail.
 *
 * This is the core lifecycle step between a {@see BreadcrumbContext} and the
 * final immutable {@see BreadcrumbTrail}. It is responsible for enforcing
 * parent-before-child ordering, carrying context parameters into nested parent
 * lookups, and rejecting cyclic definition graphs before they can recurse
 * indefinitely.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final readonly class TrailResolver
{
    public function __construct(
        private DefinitionRegistry $registry,
    ) {}

    /**
     * Resolve a context into its final immutable trail representation.
     *
     * The returned trail contains every parent item followed by the current
     * definition's own items. Missing definitions and detected cycles are
     * surfaced as exceptions rather than being ignored.
     *
     * @throws BreadcrumbCycleDetectedException
     * @throws MissingBreadcrumbDefinitionException
     */
    public function resolve(BreadcrumbContext $context): BreadcrumbTrail
    {
        return BreadcrumbTrail::fromItems($this->resolveDefinition($context, []));
    }

    /**
     * @param list<string> $stack
     *
     * Recursively resolve a single definition and any parents it references.
     *
     * The `$stack` tracks the active resolution chain in call order so cycle
     * detection can report the full breadcrumb path that led to the loop. When
     * a definition requests a parent without explicit parameters, the current
     * context parameters are inherited unchanged.
     *
     * @throws BreadcrumbCycleDetectedException
     * @throws MissingBreadcrumbDefinitionException
     * @return list<BreadcrumbItem>
     */
    private function resolveDefinition(BreadcrumbContext $context, array $stack): array
    {
        $name = $context->name();

        throw_if(in_array($name, $stack, true), BreadcrumbCycleDetectedException::forChain([...$stack, $name]));

        $nextStack = [...$stack, $name];
        $resolvedParents = [];

        $builder = new TrailBuilder(function (string $parentName, ?array $params = null) use (&$resolvedParents, $context, $nextStack): void {
            $parentContext = $context->withNameAndParams($parentName, $params ?? $context->params());

            $resolvedParents = array_merge($resolvedParents, $this->resolveDefinition($parentContext, $nextStack));
        });

        $definition = $this->registry->get($name);
        $definition->build($builder, $context);

        return [...$resolvedParents, ...$builder->items()];
    }
}
