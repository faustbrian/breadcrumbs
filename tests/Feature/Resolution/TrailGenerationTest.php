<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbDefinitionException;
use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Definitions\CategoryBreadcrumb;
use Tests\Fixtures\Definitions\HomeBreadcrumb;
use Tests\Fixtures\Definitions\PostBreadcrumb;

it('generates a typed trail with current marker', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, PostBreadcrumb::class]);

    $trail = Breadcrumbs::trail('post.show', ['post' => ['id' => 42, 'title' => 'Post 42']]);

    expect($trail)->toHaveCount(2)
        ->and($trail->items()[0]->label())->toBe('Home')
        ->and($trail->items()[0]->isCurrent())->toBeFalse()
        ->and($trail->items()[1]->label())->toBe('Post 42')
        ->and($trail->items()[1]->isCurrent())->toBeTrue();
});

it('throws for missing definitions', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class]);

    Breadcrumbs::trail('missing.page');
})->throws(MissingBreadcrumbDefinitionException::class);

it('allows items without urls', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, CategoryBreadcrumb::class]);

    $trail = Breadcrumbs::trail('category.show', ['category' => ['title' => 'Tech']]);

    expect($trail->items()[1]->url())->toBeNull();
});
