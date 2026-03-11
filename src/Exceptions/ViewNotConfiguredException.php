<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

/**
 * Thrown when HTML rendering is requested before a breadcrumb view is configured.
 *
 * The package can resolve trails into array and JSON-LD formats without a Blade
 * view, but `render()` requires an explicit template. This exception gives the
 * rendering layer a deterministic failure mode instead of letting Laravel fail
 * later with a missing view name or null template reference.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ViewNotConfiguredException extends BaseException
{
    /**
     * Create an exception for missing breadcrumb view configuration.
     *
     * Raised when neither the per-call override nor the package configuration
     * provides a view name, so the manager cannot continue into the Blade
     * rendering stage.
     */
    public static function forBreadcrumbsView(): self
    {
        return new self('Breadcrumb view is not configured. Set breadcrumbs.view.');
    }
}
