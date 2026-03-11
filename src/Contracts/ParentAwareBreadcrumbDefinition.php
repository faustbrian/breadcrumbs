<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

/**
 * Optional contract for definitions that can describe their parent edges.
 *
 * Runtime trail building resolves parents imperatively through `TrailBuilder`,
 * but validation and static analysis need a declarative way to inspect the
 * definition graph without executing build logic. Definitions implement this
 * contract when their parent names are knowable ahead of time.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface ParentAwareBreadcrumbDefinition
{
    /**
     * Return parent definition names in the order they are expected to resolve.
     *
     * The returned sequence is consumed by graph validation, so it should match
     * the effective parent traversal order used during `build()`. Unknown or
     * conditional parents should be omitted unless they are guaranteed to be
     * resolvable for every build of the definition.
     *
     * @return list<string>
     */
    public function parents(): array;
}
