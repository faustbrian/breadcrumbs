<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

/**
 * Contract for breadcrumb items that can be serialized to array payloads.
 *
 * @author Brian Faust <brian@cline.sh>
 * @template TPayload of array<string, mixed>
 */
interface ArrayableBreadcrumbItem
{
    /**
     * @return TPayload
     */
    public function toArray(): array;
}
