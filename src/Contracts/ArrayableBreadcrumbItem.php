<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

/**
 * Contract for breadcrumb items that can expose a stable array payload.
 *
 * Serializers and presentation layers use this abstraction when they need a
 * transport-friendly representation without depending on a concrete item type.
 * Implementations should return a payload whose shape is stable enough to be
 * consumed repeatedly within the package, not an ad hoc debug dump.
 *
 * @author Brian Faust <brian@cline.sh>
 * @template TPayload of array<string, mixed>
 */
interface ArrayableBreadcrumbItem
{
    /**
     * Convert the item into its canonical serialization payload.
     *
     * The returned structure should preserve the semantic fields required to
     * render or further serialize the breadcrumb outside the original object
     * instance.
     *
     * @return TPayload
     */
    public function toArray(): array;
}
