<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

/**
 * Raised when breadcrumb cache directory creation fails.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnableToCreateBreadcrumbCacheDirectoryException extends BaseException
{
    public static function forPath(): self
    {
        return new self('Unable to create breadcrumb cache directory.');
    }
}
