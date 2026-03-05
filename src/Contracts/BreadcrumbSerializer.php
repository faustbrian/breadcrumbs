<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

use Cline\Breadcrumbs\Core\BreadcrumbTrail;

/**
 * Serializes a breadcrumb trail into a transport-friendly payload.
 * @author Brian Faust <brian@cline.sh>
 */
interface BreadcrumbSerializer
{
    public function serialize(BreadcrumbTrail $trail): mixed;
}
