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
 * Builds breadcrumb trails by recursively resolving parent definitions.
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
     * Resolve a breadcrumb trail for the given context.
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
