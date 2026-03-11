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
 * Thrown when breadcrumb context data cannot be reduced to route-safe scalars.
 *
 * Breadcrumb callbacks may pass nested parameter arrays or `UrlRoutable`
 * instances when referring to parent trails or generating serialized output.
 * This exception marks the point where normalization encounters a value that
 * cannot be represented as a stable scalar payload, so downstream serializers
 * and URL generation never receive ambiguous objects or resources.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedBreadcrumbParameterTypeException extends BaseException
{
    /**
     * Create an exception for an unsupported parameter discovered during normalization.
     *
     * The `$key` argument is expected to include any nested path segments already
     * traversed by the normalizer so the failure message identifies the exact
     * location of the invalid value inside the breadcrumb parameter payload.
     *
     * @param string $name Breadcrumb definition name.
     * @param string $key  Parameter key, including nested path segments.
     * @param string $type Unsupported PHP debug type.
     */
    public static function forContext(string $name, string $key, string $type): self
    {
        return new self(sprintf('Breadcrumb [%s] parameter [%s] has unsupported type [%s].', $name, $key, $type));
    }
}
