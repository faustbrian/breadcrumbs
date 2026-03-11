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
 * Thrown when a configured serializer format resolves to an incompatible service.
 *
 * Serializer lookup in {@see \Cline\Breadcrumbs\Serialization\SerializerRegistry}
 * happens after format resolution but before any trail payload is produced. A
 * format may exist in configuration and still be invalid if the configured
 * class cannot be instantiated as a breadcrumb serializer or resolves to a
 * different type from the container.
 *
 * This exception marks a package wiring error: the format key was found, but
 * the resolved implementation cannot fulfill the serializer contract needed by
 * methods such as `serialize()`, `asArray()`, `jsonLd()`, or `toResponse()`.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBreadcrumbSerializerException extends BaseException
{
    /**
     * Create an exception for a serializer mapping that resolves to the wrong type.
     *
     * The format was present in configuration, so failure occurs only after the
     * container attempts to resolve the mapped class. No serialization side
     * effects occur because execution stops before the trail is handed to the
     * serializer implementation.
     */
    public static function forFormat(string $format, string $serializerClass): self
    {
        return new self(sprintf(
            'Serializer [%s] for format [%s] must implement [%s].',
            $serializerClass,
            $format,
            'Cline\\Breadcrumbs\\Contracts\\BreadcrumbSerializer',
        ));
    }
}
