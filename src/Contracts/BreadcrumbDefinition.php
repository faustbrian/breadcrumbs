<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

/**
 * Contract for a resolvable node in the breadcrumb definition graph.
 *
 * A definition owns the logic for one named breadcrumb and is responsible for
 * appending its portion of the final trail when asked to build. The registry
 * and builders treat the `name()` result as the canonical identifier, so
 * implementations must keep it stable and globally unique within the package.
 *
 * Definitions participate in trail construction through `TrailBuilder`, which
 * allows them to add local items and delegate to parent definitions. They do
 * not return items directly because parent expansion and append ordering are
 * coordinated by the builder workflow.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface BreadcrumbDefinition
{
    /**
     * Return the unique registry key for this definition.
     *
     * The name is used for lookup, parent references, caching, and validation,
     * so changing it is a breaking change for any callers that resolve this
     * breadcrumb by name.
     */
    public function name(): string;

    /**
     * Append this definition's contribution to the active trail build.
     *
     * Implementations should use the builder to control append order. Parent
     * definitions may be resolved before or after local items depending on the
     * desired trail shape, and any missing context requirements should surface
     * as exceptions rather than being silently ignored.
     */
    public function build(TrailBuilder $trail, BreadcrumbContext $context): void;
}
