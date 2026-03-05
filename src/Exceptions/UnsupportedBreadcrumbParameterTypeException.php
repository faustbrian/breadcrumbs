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
 * Raised when a breadcrumb parameter cannot be normalized to a scalar.
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedBreadcrumbParameterTypeException extends BaseException
{
    /**
     * @param string $name Breadcrumb definition name.
     * @param string $key  Parameter key, including nested path segments.
     * @param string $type Unsupported PHP debug type.
     */
    public static function forContext(string $name, string $key, string $type): self
    {
        return new self(sprintf('Breadcrumb [%s] parameter [%s] has unsupported type [%s].', $name, $key, $type));
    }
}
