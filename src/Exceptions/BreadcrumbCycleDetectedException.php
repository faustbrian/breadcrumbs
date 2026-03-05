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
 * Raised when recursive breadcrumb resolution detects a cycle.
 * @author Brian Faust <brian@cline.sh>
 */
final class BreadcrumbCycleDetectedException extends BaseException
{
    /**
     * @param list<string> $chain Breadcrumb names in detected cycle order.
     */
    public static function forChain(array $chain): self
    {
        return new self('Breadcrumb cycle detected: '.implode(' -> ', $chain));
    }
}
