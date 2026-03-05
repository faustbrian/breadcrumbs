<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Discovery;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class DiscoveredBreadcrumb implements BreadcrumbDefinition
{
    public function name(): string
    {
        return 'discovered';
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $trail->push('Discovered');
    }
}
