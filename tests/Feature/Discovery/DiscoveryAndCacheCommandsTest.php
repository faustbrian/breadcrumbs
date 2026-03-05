<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Discovery\DiscoveredBreadcrumb;

it('discovers definition classes from configured paths', function (): void {
    Config::set('breadcrumbs.definitions', []);
    Config::set('breadcrumbs.discovery.enabled', true);
    Config::set('breadcrumbs.discovery.paths', [__DIR__.'/../../Fixtures/Discovery']);

    $trail = Breadcrumbs::trail('discovered');

    expect($trail->labels())->toBe(['Discovered'])
        ->and(class_exists(DiscoveredBreadcrumb::class))->toBeTrue();
});

it('caches and clears discovered definitions through commands', function (): void {
    Config::set('breadcrumbs.definitions', []);
    Config::set('breadcrumbs.discovery.enabled', true);
    Config::set('breadcrumbs.discovery.paths', [__DIR__.'/../../Fixtures/Discovery']);

    $cachePath = base_path('bootstrap/cache/breadcrumbs-test.php');

    $this->artisan('breadcrumbs:cache')->assertSuccessful();
    expect(file_exists($cachePath))->toBeTrue();

    $this->artisan('breadcrumbs:clear')->assertSuccessful();
    expect(file_exists($cachePath))->toBeFalse();
});
