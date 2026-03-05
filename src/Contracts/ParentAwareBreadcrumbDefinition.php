<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Contracts;

/**
 * Optional contract for definitions that expose parent breadcrumb names.
 * @author Brian Faust <brian@cline.sh>
 */
interface ParentAwareBreadcrumbDefinition
{
    /**
     * Return parent breadcrumb names in resolution order.
     *
     * @return list<string>
     */
    public function parents(): array;
}
