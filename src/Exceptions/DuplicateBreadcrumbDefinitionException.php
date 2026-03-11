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
 * Thrown when definition registration would make a breadcrumb name ambiguous.
 *
 * Definition names are the primary lookup key used by
 * {@see \Cline\Breadcrumbs\Core\DefinitionRegistry} during trail resolution.
 * The registry is populated during boot-time discovery and may also receive
 * runtime registrations. Once two definitions claim the same name, the package
 * can no longer deterministically resolve a trail entry, so registration is
 * aborted immediately instead of allowing last-write-wins behavior.
 *
 * This exception therefore marks a structural configuration failure rather than
 * a recoverable missing resource. It is raised before the conflicting
 * definition is stored, leaving the registry in its previously valid state.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DuplicateBreadcrumbDefinitionException extends BaseException
{
    /**
     * Create an exception for a duplicate breadcrumb name during registration.
     *
     * This factory is used both while the registry is being assembled from
     * discovered definition classes and when a definition is registered
     * directly at runtime. The reported class is the definition that attempted
     * to claim a name that was already present.
     *
     * @param string       $name            Duplicate breadcrumb definition name.
     * @param class-string $definitionClass Duplicate definition class.
     */
    public static function forName(string $name, string $definitionClass): self
    {
        return new self(sprintf('Breadcrumb [%s] is already registered. Duplicate class: %s.', $name, $definitionClass));
    }
}
