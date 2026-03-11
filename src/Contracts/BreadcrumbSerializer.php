<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

use Cline\Breadcrumbs\Core\BreadcrumbTrail;

/**
 * Strategy for converting a built breadcrumb trail into an output payload.
 *
 * The package keeps trail construction separate from presentation so consumers
 * can serialize the same `BreadcrumbTrail` into arrays, JSON resources, view
 * models, or other transport-specific shapes without changing build logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface BreadcrumbSerializer
{
    /**
     * Convert the immutable trail into the serializer's target representation.
     *
     * Implementations should treat the supplied trail as read-only and may
     * return any payload type that is appropriate for the integration boundary.
     */
    public function serialize(BreadcrumbTrail $trail): mixed;
}
