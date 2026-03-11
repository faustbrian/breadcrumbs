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
 * Thrown when discovery yields a class that cannot participate in trail resolution.
 *
 * The package accepts definition class names from configured lists, discovery
 * scans, and cached classmaps. Before any class is instantiated, the registry
 * verifies that it implements the breadcrumb definition contract expected by
 * the resolver pipeline. This exception stops registration as soon as that
 * invariant is violated.
 *
 * Failing fast here prevents partially initialized registries, late container
 * errors, and misleading trail-resolution failures further downstream. The
 * invalid class is never instantiated or stored.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBreadcrumbDefinitionException extends BaseException
{
    /**
     * Create an exception for a discovered class that does not implement the
     * required breadcrumb definition contract.
     *
     * @param class-string $definitionClass Invalid definition class name.
     */
    public static function forDefinitionClass(string $definitionClass): self
    {
        return new self(sprintf('Breadcrumb definition class [%s] must implement BreadcrumbDefinition.', $definitionClass));
    }
}
