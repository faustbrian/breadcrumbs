<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;
use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Cline\Breadcrumbs\Routing\BreadcrumbTrail;

it('registers breadcrumbs with callback api', function (): void {
    Breadcrumbs::for('home', function (BreadcrumbTrail $trail): void {
        $trail->push('Home', '/');
    });

    Breadcrumbs::for('blog.show', function (BreadcrumbTrail $trail, string $slug): void {
        $trail->parent('home');
        $trail->push('Blog '.$slug, '/blog/'.$slug);
    });

    $trail = Breadcrumbs::trail('blog.show', ['slug' => 'intro']);

    expect($trail)->toHaveCount(2)
        ->and($trail->items()[0]->label())->toBe('Home')
        ->and($trail->items()[1]->label())->toBe('Blog intro');
});

it('supports grouped breadcrumb names with as prefixes', function (): void {
    Breadcrumbs::group(['as' => 'admin'], function (): void {
        Breadcrumbs::as('dashboard', function (BreadcrumbTrail $trail): void {
            $trail->push('Dashboard', '/admin');
        });

        Breadcrumbs::group('users', function (): void {
            Breadcrumbs::for('index', function (BreadcrumbTrail $trail): void {
                $trail->parent('admin.dashboard');
                $trail->push('Users', '/admin/users');
            });
        });
    });

    $trail = Breadcrumbs::trail('admin.users.index');

    expect($trail)->toHaveCount(2)
        ->and($trail->items()[0]->label())->toBe('Dashboard')
        ->and($trail->items()[1]->label())->toBe('Users');
});

it('injects trail builder and context into callbacks', function (): void {
    Breadcrumbs::for('contextual', function (TrailBuilder $trail, BreadcrumbContext $context, string $page = 'home'): void {
        $trail->push('Section', '/section');
        $trail->push('Page '.$context->param('page', $page), '/section/'.$context->param('page', $page));
    });

    $trail = Breadcrumbs::trail('contextual', ['page' => 'guides']);

    expect($trail->items()[1]->label())->toBe('Page guides');
});
