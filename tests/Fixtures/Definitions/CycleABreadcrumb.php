<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Definitions;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Contracts\ParentAwareBreadcrumbDefinition;
use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class CycleABreadcrumb implements BreadcrumbDefinition, ParentAwareBreadcrumbDefinition
{
    public function name(): string
    {
        return 'cycle.a';
    }

    /**
     * @return list<string>
     */
    public function parents(): array
    {
        return ['cycle.b'];
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $trail->parent('cycle.b');
        $trail->push('A');
    }
}
