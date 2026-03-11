<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

use function implode;

/**
 * Raised when breadcrumb definitions reference each other recursively.
 *
 * Trail resolution treats cycles as a hard failure because a parent chain must
 * be acyclic to produce a finite ordered breadcrumb trail.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class BreadcrumbCycleDetectedException extends BaseException
{
    /**
     * Create an exception describing the detected resolution chain.
     *
     * The provided chain is expected to include the repeated terminal name so
     * the rendered message shows the exact loop encountered by the resolver.
     *
     * @param list<string> $chain Breadcrumb names in detected cycle order.
     */
    public static function forChain(array $chain): self
    {
        return new self('Breadcrumb cycle detected: '.implode(' -> ', $chain));
    }
}
