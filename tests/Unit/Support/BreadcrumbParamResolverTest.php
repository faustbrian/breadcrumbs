<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Support\BreadcrumbParamResolver;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

it('normalizes route parameters and skips locale by default', function (): void {
    $route = new Route(['GET'], '/{locale}/posts/{post}/{page}', static fn (): null => null);
    $route->bind(Request::create('/en/posts/ignored/2', SymfonyRequest::METHOD_GET));
    $route->setParameter('post', new class() implements UrlRoutable
    {
        public function getRouteKey(): string
        {
            return 'post-42';
        }

        public function getRouteKeyName(): string
        {
            return 'id';
        }

        public function resolveRouteBinding($value, $field = null): mixed
        {
            return null;
        }

        public function resolveChildRouteBinding($childType, $value, $field): mixed
        {
            return null;
        }
    });
    $route->setParameter('page', 2);

    expect(BreadcrumbParamResolver::fromRoute($route))->toBe([
        'post' => 'post-42',
        'page' => 2,
    ]);
});

it('resolves model values from breadcrumb context', function (): void {
    $context = new BreadcrumbContext('users.show', ['user' => 7]);

    $modelClass = new class() extends Model
    {
        use HasFactory;

        protected $guarded = [];

        public function resolveRouteBinding($value, $field = null): mixed
        {
            return new self(['id' => $value]);
        }
    };

    $resolved = BreadcrumbParamResolver::resolveModel($context, 'user', $modelClass::class);

    expect($resolved)->toBeInstanceOf($modelClass::class)
        ->and($resolved->getRouteKey())->toBe(7);
});

it('returns raw values when class is not an eloquent model', function (): void {
    $context = new BreadcrumbContext('users.show', ['user' => 7]);

    $resolved = BreadcrumbParamResolver::resolveModel($context, 'user', stdClass::class);

    expect($resolved)->toBe(7);
});
