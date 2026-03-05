<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Serialization;

use Cline\Breadcrumbs\Contracts\BreadcrumbSerializer;
use Cline\Breadcrumbs\Core\BreadcrumbTrail;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class LabelsSerializer implements BreadcrumbSerializer
{
    /**
     * @return list<string>
     */
    public function serialize(BreadcrumbTrail $trail): array
    {
        return $trail->labels();
    }
}
