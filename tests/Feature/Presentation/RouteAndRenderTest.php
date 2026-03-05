<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Definitions\HomeBreadcrumb;
use Tests\Fixtures\Definitions\PostBreadcrumb;

it('resolves using the current route by default', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, PostBreadcrumb::class]);

    Route::name('post.show')->get('/posts/{post}', function (string $post): array {
        $trail = Breadcrumbs::trail();

        return $trail->labels();
    });

    $this->get('/posts/abc')->assertOk()->assertJson(['Home', 'Post abc']);
});

it('renders using the configured blade view', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class]);

    $html = Breadcrumbs::render('home')->toHtml();

    $this->assertXmlStringEqualsXmlString(
        '<ol><li class="current">Home</li></ol>',
        $html,
    );
});

it('generates schema.org breadcrumb list json-ld data', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, PostBreadcrumb::class]);

    $jsonLd = Breadcrumbs::jsonLd('post.show', ['post' => ['id' => 9, 'title' => 'Post 9']]);

    expect($jsonLd['@type'])->toBe('BreadcrumbList')
        ->and($jsonLd['itemListElement'])->toHaveCount(2)
        ->and($jsonLd['itemListElement'][0]['name'])->toBe('Home')
        ->and($jsonLd['itemListElement'][1]['name'])->toBe('Post 9');
});
