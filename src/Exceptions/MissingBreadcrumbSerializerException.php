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
 * Raised when a requested serializer format is not configured.
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingBreadcrumbSerializerException extends BaseException
{
    public static function forFormat(string $format): self
    {
        return new self(sprintf('No serializer configured for breadcrumb format [%s].', $format));
    }
}
