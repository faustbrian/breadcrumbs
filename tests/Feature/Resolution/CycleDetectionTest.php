<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Exceptions\BreadcrumbCycleDetectedException;
use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Definitions\CycleABreadcrumb;
use Tests\Fixtures\Definitions\CycleBBreadcrumb;

it('detects parent cycles', function (): void {
    Config::set('breadcrumbs.definitions', [CycleABreadcrumb::class, CycleBBreadcrumb::class]);

    Breadcrumbs::trail('cycle.a');
})->throws(BreadcrumbCycleDetectedException::class);
