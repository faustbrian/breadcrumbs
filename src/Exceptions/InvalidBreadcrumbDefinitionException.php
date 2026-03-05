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
 * Raised when a discovered definition does not implement the contract.
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBreadcrumbDefinitionException extends BaseException
{
    /**
     * @param class-string $definitionClass Invalid definition class name.
     */
    public static function forDefinitionClass(string $definitionClass): self
    {
        return new self(sprintf('Breadcrumb definition class [%s] must implement BreadcrumbDefinition.', $definitionClass));
    }
}
