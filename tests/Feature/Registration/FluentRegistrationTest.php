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
use Tests\Fixtures\Models\ResolvableUser;

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

it('allows later callback registrations to override previous ones', function (): void {
    Breadcrumbs::for('home', function (BreadcrumbTrail $trail): void {
        $trail->push('Home', '/');
    });

    Breadcrumbs::for('home', function (BreadcrumbTrail $trail): void {
        $trail->push('Dashboard', '/dashboard');
    });

    $trail = Breadcrumbs::trail('home');

    expect($trail->labels())->toBe(['Dashboard'])
        ->and($trail->items()[0]->url())->toBe('/dashboard');
});

it('resolves typed eloquent model parameters for callback breadcrumbs', function (): void {
    Breadcrumbs::for('users.show', function (BreadcrumbTrail $trail, ResolvableUser $user): void {
        $trail->push('Users', '/users');
        $trail->push('User '.$user->getRouteKey(), '/users/'.$user->getRouteKey());
    });

    $trail = Breadcrumbs::trail('users.show', ['user' => 7]);

    expect($trail->labels())->toBe(['Users', 'User 7'])
        ->and($trail->items()[1]->url())->toBe('/users/7');
});

it('supports passing typed callback models to parent breadcrumbs', function (): void {
    Breadcrumbs::for('users.show', function (BreadcrumbTrail $trail, ResolvableUser $user): void {
        $trail->push('Users', '/users');
        $trail->push('User '.$user->getRouteKey(), '/users/'.$user->getRouteKey());
    });

    Breadcrumbs::for('users.edit', function (BreadcrumbTrail $trail, ResolvableUser $user): void {
        $trail->parent('users.show', ['user' => $user]);
        $trail->push('Edit', '/users/'.$user->getRouteKey().'/edit');
    });

    $trail = Breadcrumbs::trail('users.edit', ['user' => 7]);

    expect($trail->labels())->toBe(['Users', 'User 7', 'Edit'])
        ->and($trail->items()[2]->url())->toBe('/users/7/edit');
});
