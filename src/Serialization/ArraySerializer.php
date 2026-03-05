<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Serialization;

use Cline\Breadcrumbs\Contracts\BreadcrumbSerializer;
use Cline\Breadcrumbs\Core\BreadcrumbTrail;
use Illuminate\Container\Attributes\Singleton;

/**
 * Serializes a breadcrumb trail into item array payloads.
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class ArraySerializer implements BreadcrumbSerializer
{
    /**
     * @return list<array<string, mixed>>
     */
    public function serialize(BreadcrumbTrail $trail): array
    {
        return $trail->toArray();
    }
}
