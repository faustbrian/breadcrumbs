<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\BreadcrumbItem;
use Cline\Breadcrumbs\Core\BreadcrumbTrail;
use Cline\Breadcrumbs\Exceptions\UnsupportedBreadcrumbParameterTypeException;

it('reads context parameters and defaults', function (): void {
    $context = new BreadcrumbContext('post.show', ['post' => 42]);

    expect($context->name())->toBe('post.show')
        ->and($context->param('post'))->toBe(42)
        ->and($context->param('missing', 'default'))->toBe('default');
});

it('marks the final trail item as current', function (): void {
    $trail = BreadcrumbTrail::fromItems([
        new BreadcrumbItem('Home', '/'),
        new BreadcrumbItem('Post', '/posts/1'),
    ]);

    expect($trail->items()[0]->isCurrent())->toBeFalse()
        ->and($trail->items()[1]->isCurrent())->toBeTrue()
        ->and($trail->labels())->toBe(['Home', 'Post']);
});

it('rejects unsupported parameter types', function (): void {
    new BreadcrumbContext('post.show', ['post' => new stdClass()]);
})->throws(UnsupportedBreadcrumbParameterTypeException::class);
