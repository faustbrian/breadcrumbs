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
 * Raised when serializer output payload does not match expected shape.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBreadcrumbSerializerPayloadException extends BaseException
{
    public static function arrayRequiredForFormat(string $format): self
    {
        return new self(sprintf('Serializer [%s] must return an array payload.', $format));
    }
}
