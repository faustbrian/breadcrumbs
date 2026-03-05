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
 * Raised when multiple classes register the same breadcrumb name.
 * @author Brian Faust <brian@cline.sh>
 */
final class DuplicateBreadcrumbDefinitionException extends BaseException
{
    /**
     * @param string       $name            Duplicate breadcrumb definition name.
     * @param class-string $definitionClass Duplicate definition class.
     */
    public static function forName(string $name, string $definitionClass): self
    {
        return new self(sprintf('Breadcrumb [%s] is already registered. Duplicate class: %s.', $name, $definitionClass));
    }
}
