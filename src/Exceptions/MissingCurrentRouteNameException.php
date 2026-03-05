<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

/**
 * Raised when no current route name can be resolved.
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingCurrentRouteNameException extends BaseException
{
    /**
     * Create an exception for unavailable or unnamed current routes.
     */
    public static function forCurrentRoute(): self
    {
        return new self('Current route is not available or is unnamed.');
    }
}
