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
 * Raised when a requested breadcrumb definition is not registered.
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingBreadcrumbDefinitionException extends BaseException
{
    /**
     * @param string $name Missing breadcrumb definition name.
     */
    public static function forName(string $name): self
    {
        return new self(sprintf('No breadcrumb definition is registered for [%s].', $name));
    }
}
