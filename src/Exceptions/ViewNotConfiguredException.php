<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

/**
 * Raised when breadcrumb rendering is requested without a valid view.
 * @author Brian Faust <brian@cline.sh>
 */
final class ViewNotConfiguredException extends BaseException
{
    /**
     * Create an exception for invalid breadcrumb view configuration.
     */
    public static function forBreadcrumbsView(): self
    {
        return new self('Breadcrumb view is not configured. Set breadcrumbs.view.');
    }
}
