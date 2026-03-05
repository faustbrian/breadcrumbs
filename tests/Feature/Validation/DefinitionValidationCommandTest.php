<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Definitions\BrokenParentBreadcrumb;
use Tests\Fixtures\Definitions\CycleABreadcrumb;
use Tests\Fixtures\Definitions\CycleBBreadcrumb;
use Tests\Fixtures\Definitions\HomeBreadcrumb;

it('passes validation for valid definition graph', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class]);

    $this->artisan('breadcrumbs:validate')
        ->expectsOutput('Breadcrumb definitions are valid.')
        ->assertSuccessful();
});

it('fails validation for missing parents and cycles', function (): void {
    Config::set('breadcrumbs.definitions', [BrokenParentBreadcrumb::class, CycleABreadcrumb::class, CycleBBreadcrumb::class]);

    $this->artisan('breadcrumbs:validate')
        ->expectsOutputToContain('Missing parent: broken.parent -> missing.parent')
        ->expectsOutputToContain('Cycle: cycle.a -> cycle.b -> cycle.a')
        ->assertFailed();
});
