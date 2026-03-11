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
 * Thrown when a serializer returns a payload shape that violates a typed API contract.
 *
 * The manager exposes strongly expected payloads for certain convenience
 * methods, notably `jsonLd()` and `asArray()`. Those methods first delegate to
 * the configured serializer and then verify that the returned value matches the
 * container-friendly array shape they promise to callers. This exception is the
 * guardrail that stops incompatible serializer output from leaking past those
 * typed entry points.
 *
 * It is therefore a post-serialization validation failure: serializer
 * resolution succeeded and the serializer ran, but the returned payload does
 * not satisfy the format-specific invariant required by the manager API.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBreadcrumbSerializerPayloadException extends BaseException
{
    /**
     * Create an exception for a serializer format that must return an array payload.
     *
     * This is used by manager methods whose public contract promises an array
     * result for the requested format. A non-array payload is treated as a hard
     * failure even if it could still be JSON-encoded, because callers rely on a
     * predictable in-memory structure before response generation.
     */
    public static function arrayRequiredForFormat(string $format): self
    {
        return new self(sprintf('Serializer [%s] must return an array payload.', $format));
    }
}
