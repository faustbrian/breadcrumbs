<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

/**
 * Thrown when the package cannot derive a named route context from the current request.
 *
 * Route-based convenience methods rely on
 * {@see \Cline\Breadcrumbs\Core\RouteContextResolver} to transform the current
 * Laravel route into a breadcrumb context. That resolver requires both a
 * current route instance and a non-null route name because definition lookup is
 * keyed strictly by name.
 *
 * This exception is therefore specific to implicit resolution paths such as
 * `trail()`, `render()`, `jsonLd()`, or `toResponse()` when they are invoked
 * without an explicit breadcrumb name. Callers can avoid it by passing a name
 * directly instead of depending on the request lifecycle state.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingCurrentRouteNameException extends BaseException
{
    /**
     * Create an exception for unavailable or unnamed current routes.
     *
     * The failure is raised before route parameters are extracted, so no
     * breadcrumb context is created and no trail resolution work begins.
     */
    public static function forCurrentRoute(): self
    {
        return new self('Current route is not available or is unnamed.');
    }
}
