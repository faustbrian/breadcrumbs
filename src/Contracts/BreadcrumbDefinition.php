<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

/**
 * Contract for named breadcrumb definitions.
 * @author Brian Faust <brian@cline.sh>
 */
interface BreadcrumbDefinition
{
    /**
     * Get the unique breadcrumb definition name.
     */
    public function name(): string;

    /**
     * Build trail items for the provided breadcrumb context.
     *
     * Implementations should append items and optionally resolve parent
     * definitions through the provided trail builder.
     */
    public function build(TrailBuilder $trail, BreadcrumbContext $context): void;
}
