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
 * Thrown when serialization is requested for a format that has no registry entry.
 *
 * Serializer resolution is format-first: the manager asks the serializer
 * registry for a named format, and only then can the container resolve the
 * implementation. This exception covers the earliest failure point in that
 * process, where the format key itself is absent from configuration.
 *
 * No serializer is instantiated and no trail transformation occurs. Callers
 * typically see this from `serialize()`, `jsonLd()`, `asArray()`, or
 * `toResponse()` when they request a custom format that was never registered.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingBreadcrumbSerializerException extends BaseException
{
    /**
     * Create an exception for an unknown serializer format.
     *
     * This is raised before container resolution, so the failure indicates
     * configuration omission rather than an invalid serializer class.
     */
    public static function forFormat(string $format): self
    {
        return new self(sprintf('No serializer configured for breadcrumb format [%s].', $format));
    }
}
