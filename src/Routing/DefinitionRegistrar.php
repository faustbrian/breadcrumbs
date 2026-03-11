<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Routing;

use Cline\Breadcrumbs\Core\DefinitionRegistry;
use Closure;
use Illuminate\Container\Attributes\Singleton;

use function array_key_exists;
use function array_pop;
use function implode;
use function is_string;
use function mb_rtrim;

/**
 * Registers closure-based breadcrumb definitions into the shared definition registry.
 *
 * This registrar is the route-file facing writer for breadcrumb definitions. It
 * applies nested name prefixes, converts closures into runtime definition
 * objects, and ensures grouped registrations unwind their temporary prefix
 * state even when a callback throws.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class DefinitionRegistrar
{
    /** @var list<string> */
    private array $namePrefixes = [];

    public function __construct(
        private readonly DefinitionRegistry $registry,
    ) {}

    /**
     * Register a breadcrumb callback under its fully qualified definition name.
     *
     * Group prefixes are applied at registration time, not lazily during lookup,
     * so the registry stores the final canonical name that trail resolution will
     * later request.
     */
    public function for(string $name, Closure $callback): void
    {
        $this->registry->upsert(
            new CallbackBreadcrumbDefinition($this->qualifyName($name), $callback),
        );
    }

    /**
     * Register a breadcrumb callback using the route-style alias syntax.
     *
     * This exists for API familiarity with Laravel route registration where
     * `as()` communicates naming intent more directly than `for()`.
     */
    public function as(string $name, Closure $callback): void
    {
        $this->for($name, $callback);
    }

    /**
     * Apply a temporary name prefix while registering nested breadcrumb definitions.
     *
     * String attributes are treated as the prefix directly. Array attributes only
     * contribute when they contain a string `as` entry. Prefixes are normalized to
     * exactly one trailing dot, and the previous prefix stack is always restored in
     * a `finally` block so failed registrations do not leak naming state.
     *
     * @param array<string, mixed>|string $attributes
     */
    public function group(array|string $attributes, Closure $callback): mixed
    {
        $as = $this->resolveGroupPrefix($attributes);

        if ($as === '') {
            return $callback();
        }

        $this->namePrefixes[] = $as;

        try {
            return $callback();
        } finally {
            array_pop($this->namePrefixes);
        }
    }

    /**
     * Combine the current group prefix stack with a local breadcrumb name.
     */
    private function qualifyName(string $name): string
    {
        if ($this->namePrefixes === []) {
            return $name;
        }

        return $this->currentPrefix().$name;
    }

    /**
     * Collapse nested prefixes into a canonical dotted prefix string.
     *
     * Empty trailing separators are trimmed before a single dot is re-applied,
     * preventing accidental double separators when callers mix dotted and
     * non-dotted prefixes.
     */
    private function currentPrefix(): string
    {
        return mb_rtrim(implode('', $this->namePrefixes), '.').'.';
    }

    /**
     * Resolve the name prefix contributed by a group declaration.
     *
     * Unsupported attribute shapes are ignored rather than rejected so group
     * declarations can share the same attribute array style as Laravel routes
     * without forcing unrelated keys to be stripped beforehand.
     *
     * @param array<string, mixed>|string $attributes
     */
    private function resolveGroupPrefix(array|string $attributes): string
    {
        if (is_string($attributes)) {
            return mb_rtrim($attributes, '.').'.';
        }

        if (array_key_exists('as', $attributes) && is_string($attributes['as'])) {
            return mb_rtrim($attributes['as'], '.').'.';
        }

        return '';
    }
}
