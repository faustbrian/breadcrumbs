<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

use function sprintf;

/**
 * Raised when a breadcrumb parameter is required but missing.
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingBreadcrumbParameterException extends BaseException
{
    /**
     * @param string $name  Breadcrumb definition name.
     * @param string $param Missing required parameter key.
     */
    public static function forContext(string $name, string $param): self
    {
        return new self(sprintf('Breadcrumb [%s] requires parameter [%s].', $name, $param));
    }
}
