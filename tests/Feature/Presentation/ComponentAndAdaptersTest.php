<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Definitions\HomeBreadcrumb;
use Tests\Fixtures\Definitions\PostBreadcrumb;

it('renders breadcrumbs through blade component', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class]);

    $html = Blade::render('<x-breadcrumbs-trail name="home" />');

    expect($html)->toContain('Home')
        ->and($html)->toContain('class="current"');
});

it('provides array and json response adapters', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, PostBreadcrumb::class]);

    $array = Breadcrumbs::asArray('post.show', ['post' => ['id' => 22, 'title' => 'Post 22']]);
    $trailResponse = Breadcrumbs::toResponse('post.show', ['post' => ['id' => 22, 'title' => 'Post 22']]);
    $jsonLdResponse = Breadcrumbs::toResponse('post.show', ['post' => ['id' => 22, 'title' => 'Post 22']], 'jsonld');

    expect($array)->toHaveCount(2)
        ->and($array[1]['label'])->toBe('Post 22')
        ->and($trailResponse->getData(true)[1]['label'])->toBe('Post 22')
        ->and($jsonLdResponse->getData(true)['@type'])->toBe('BreadcrumbList');
});
