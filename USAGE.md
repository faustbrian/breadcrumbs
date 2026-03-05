# Usage

This package provides typed, class-based breadcrumbs for Laravel with explicit
contracts, discovery and cache tooling, JSON adapters, and Blade rendering.

## Table of contents

1. Requirements
2. Installation
3. Core concepts
4. Configuration
5. Creating breadcrumb definitions
6. Parent chains and inheritance
7. Working with route/context parameters
8. Resolving and rendering breadcrumbs
9. Blade component
10. Discovery and caching
11. Validation command
12. Output formats
13. Error handling
14. Architecture notes

## Requirements

- PHP 8.5+
- Laravel 10+

## Installation

```bash
composer require cline/breadcrumbs
```

Publish config (optional, but recommended):

```bash
php artisan vendor:publish --tag=breadcrumbs-config
```

## Core concepts

- A breadcrumb is defined as a class implementing
  `Cline\Breadcrumbs\Contracts\BreadcrumbDefinition`.
- Every definition has a unique route-like `name()` and a `build()` method.
- `TrailBuilder` builds the ordered breadcrumb trail.
- `BreadcrumbContext` carries normalized route/input parameters.
- `BreadcrumbsManager` is the runtime API for resolving/rendering trails.

## Configuration

`config/breadcrumbs.php`:

```php
<?php

declare(strict_types=1);

return [
    'view' => 'breadcrumbs',

    'definitions' => [
        // App\Breadcrumbs\HomeBreadcrumb::class,
    ],

    'discovery' => [
        'enabled' => false,
        'paths' => [
            app_path('Breadcrumbs'),
        ],
        'classmap_paths' => [
            base_path('vendor/composer/autoload_classmap.php'),
        ],
    ],

    'callbacks' => [
        'autoload' => [
            base_path('routes/breadcrumbs/*.php'),
            base_path('modules/*/Routes/breadcrumbs.php'),
        ],
    ],

    'cache' => [
        'enabled' => true,
        'path' => base_path('bootstrap/cache/breadcrumbs.php'),
    ],

    'serializers' => [
        'trail' => Cline\Breadcrumbs\Serialization\ArraySerializer::class,
        'jsonld' => Cline\Breadcrumbs\Serialization\JsonLdSerializer::class,
    ],
];
```

Options:

- `view`: required default Blade view used by `Breadcrumbs::render()`.
- `definitions`: explicit class list of breadcrumb definitions.
- `discovery.enabled`: enable class discovery from configured paths.
- `discovery.paths`: directories scanned for definition classes.
- `discovery.classmap_paths`: optional classmap files used for discovery.
- `cache.enabled`: enable discovery result caching.
- `cache.path`: cache file location for discovered class names.
- `callbacks.autoload`: glob patterns for route-style callback registration
  files auto-loaded at boot.
- `serializers`: output serializer class map by format name.

## Creating breadcrumb definitions

Minimal definition:

```php
<?php

declare(strict_types=1);

namespace App\Breadcrumbs;

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Core\TrailBuilder;

final class HomeBreadcrumb implements BreadcrumbDefinition
{
    public function name(): string
    {
        return 'home';
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $trail->push('Home', route('home'));
    }
}
```

Route-style callback API is also supported when you prefer inline
registration:

```php
use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Cline\Breadcrumbs\Routing\BreadcrumbTrail;

Breadcrumbs::for('home', function (BreadcrumbTrail $trail): void {
    $trail->push('Home', route('home'));
});

Breadcrumbs::group(['as' => 'blog'], function (): void {
    Breadcrumbs::for('index', function (BreadcrumbTrail $trail): void {
        $trail->parent('home');
        $trail->push('Blog', route('blog.index'));
    });
});
```

Notes:

- `Breadcrumbs::as()` is an alias of `Breadcrumbs::for()`.
- `group()` supports string prefixes (for example, `'admin'`) or route-like
  arrays (`['as' => 'admin']`).
- Callback parameters after the trail are resolved by name from breadcrumb
  context params. You can also type-hint `BreadcrumbContext`.

Create your own Blade template (for example, `resources/views/breadcrumbs.blade.php`)
because this package intentionally ships no default UI:

```blade
@if ($trail->isNotEmpty())
    <nav aria-label="Breadcrumb">
        <ol>
            @foreach ($trail as $breadcrumb)
                <li>
                    @if ($breadcrumb->url() && ! $breadcrumb->isCurrent())
                        <a href="{{ $breadcrumb->url() }}">{{ $breadcrumb->label() }}</a>
                    @else
                        <span aria-current="{{ $breadcrumb->isCurrent() ? 'page' : 'false' }}">
                            {{ $breadcrumb->label() }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
```

## Parent chains and inheritance

For static parent metadata (used by validator/tooling), implement
`ParentAwareBreadcrumbDefinition`.

```php
use Cline\Breadcrumbs\Contracts\ParentAwareBreadcrumbDefinition;

final class PostShowBreadcrumb implements BreadcrumbDefinition, ParentAwareBreadcrumbDefinition
{
    public function name(): string
    {
        return 'post.show';
    }

    /** @return list<string> */
    public function parents(): array
    {
        return ['home'];
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $post = $context->param('post');

        $trail->parent('home');
        $trail->push((string) $post['title'], route('post.show', $post['id']));
    }
}
```

Notes:

- Use `$trail->parent('name')` to include parent trails at runtime.
- Static `parents()` and runtime `parent()` should describe the same chain.
- Cycle detection is enforced during resolution.

## Working with route/context parameters

`BreadcrumbContext` enforces normalized parameter values for predictable,
serializable behavior.

Allowed parameter value types:

- `null`
- scalar values: `string`, `int`, `float`, `bool`
- `BackedEnum` (stored as backing value)
- `UnitEnum` (stored as case name)
- `Stringable` (cast to string)
- arrays of allowed values (recursive)

Reading params:

```php
$post = $context->param('post');
$id = $context->param('id', 0); // default if missing
```

If a required key is missing and no default is provided,
`MissingBreadcrumbParameterException` is thrown.

## Resolving and rendering breadcrumbs

Facade examples:

```php
use Cline\Breadcrumbs\Facades\Breadcrumbs;

$trail = Breadcrumbs::trail('post.show', ['post' => ['id' => 1, 'title' => 'Post 1']]);
$trailFromRoute = Breadcrumbs::trail();

$view = Breadcrumbs::render();
$customView = Breadcrumbs::render('post.show', ['post' => ['id' => 1, 'title' => 'Post 1']], 'breadcrumbs.admin');
```

Manager adapters:

```php
$array = Breadcrumbs::asArray('post.show', ['post' => ['id' => 1, 'title' => 'Post 1']]);
$json = Breadcrumbs::toResponse('post.show', ['post' => ['id' => 1, 'title' => 'Post 1']]);
$jsonLd = Breadcrumbs::toResponse('post.show', ['post' => ['id' => 1, 'title' => 'Post 1']], 'jsonld');
$labels = Breadcrumbs::toResponse('post.show', ['post' => ['id' => 1, 'title' => 'Post 1']], 'labels');
```

## Blade component

Component using configured default view (`breadcrumbs.view`):

```blade
<x-breadcrumbs-trail
    name="post.show"
    :params="['post' => ['id' => 1, 'title' => 'Post 1']]"
/>
```

Component with explicit per-use override:

```blade
<x-breadcrumbs-trail
    name="post.show"
    :params="['post' => ['id' => 1, 'title' => 'Post 1']]"
    view="breadcrumbs.admin"
/>
```

## Discovery and caching

When discovery is enabled, definition classes are found automatically from
`discovery.paths` and merged with explicit `definitions`.

Commands:

```bash
php artisan breadcrumbs:cache
php artisan breadcrumbs:clear
```

Recommended workflow:

1. Enable discovery and configure paths.
2. Warm cache in deploy/build with `breadcrumbs:cache`.
3. Clear cache after definition class moves/renames with `breadcrumbs:clear`.

Custom classmap files can be configured via `discovery.classmap_paths` when
definitions are not available through the default Composer classmap.

## Validation command

Validate definitions and parent references:

```bash
php artisan breadcrumbs:validate
```

The validator checks things like:

- parent references to undefined breadcrumbs
- duplicate definition names
- invalid definition structures

## Output formats

### Trail array

`Breadcrumbs::asArray()` and `Breadcrumbs::toResponse(..., 'trail')` return
items similar to:

```php
[
    [
        'label' => 'Home',
        'url' => 'https://example.test/',
        'current' => false,
        'meta' => [],
        'attributes' => [],
    ],
]
```

### JSON-LD

`Breadcrumbs::jsonLd()` and `Breadcrumbs::toResponse(..., 'jsonld')` return
Schema.org breadcrumb format:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'https://example.test/',
        ],
    ],
]
```

## Error handling

Common exceptions:

- `MissingCurrentRouteNameException`
- `MissingBreadcrumbDefinitionException`
- `DuplicateBreadcrumbDefinitionException`
- `InvalidBreadcrumbDefinitionException`
- `MissingBreadcrumbParameterException`
- `UnsupportedBreadcrumbParameterTypeException`
- `BreadcrumbCycleDetectedException`
- `ViewNotConfiguredException`

## Architecture notes

- `src/ServiceProvider.php` is the package entrypoint for bindings and commands.
- `src/Core/*` contains the runtime breadcrumb model and resolution pipeline.
- `src/Facades/Breadcrumbs.php` is the facade API surface.
- `src/Discovery/*`, `src/Validation/*`, `src/Console/Commands/*`, and
  `src/View/Components/*` contain supporting adapters/tooling.
