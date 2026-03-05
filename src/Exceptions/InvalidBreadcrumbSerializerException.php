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
 * Raised when a configured breadcrumb serializer class is invalid.
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBreadcrumbSerializerException extends BaseException
{
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
