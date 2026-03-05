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
 * Runtime registrar for route-style breadcrumb callback definitions.
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

    public function for(string $name, Closure $callback): void
    {
        $this->registry->register(
            new CallbackBreadcrumbDefinition($this->qualifyName($name), $callback),
        );
    }

    public function as(string $name, Closure $callback): void
    {
        $this->for($name, $callback);
    }

    /**
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

    private function qualifyName(string $name): string
    {
        if ($this->namePrefixes === []) {
            return $name;
        }

        return $this->currentPrefix().$name;
    }

    private function currentPrefix(): string
    {
        return mb_rtrim(implode('', $this->namePrefixes), '.').'.';
    }

    /**
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
