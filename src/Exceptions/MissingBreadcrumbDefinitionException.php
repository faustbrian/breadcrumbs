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
 * Thrown when trail resolution requests a breadcrumb name that the registry cannot supply.
 *
 * Breadcrumb names are resolved against the immutable snapshot held by
 * {@see \Cline\Breadcrumbs\Core\DefinitionRegistry}. This exception is raised
 * when a requested name is absent from that registry, whether the request came
 * from an explicit trail lookup, a route-derived context, or a parent
 * definition reference during recursive trail expansion.
 *
 * In practice this signals a missing registration, a typo in a breadcrumb
 * name, or stale cache/discovery data. Resolution stops at the first missing
 * node because the package cannot synthesize fallback breadcrumb definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingBreadcrumbDefinitionException extends BaseException
{
    /**
     * Create an exception for a breadcrumb name that could not be resolved.
     *
     * @param string $name Missing breadcrumb definition name.
     */
    public static function forName(string $name): self
    {
        return new self(sprintf('No breadcrumb definition is registered for [%s].', $name));
    }
}
