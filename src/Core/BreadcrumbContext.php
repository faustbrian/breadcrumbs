<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use BackedEnum;
use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbParameterException;
use Cline\Breadcrumbs\Exceptions\UnsupportedBreadcrumbParameterTypeException;
use Stringable;
use UnitEnum;

use function array_key_exists;
use function func_num_args;
use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * Immutable breadcrumb name and parameter context.
 *
 * @phpstan-type BreadcrumbParamScalar null|bool|int|float|string
 * @phpstan-type BreadcrumbParamValue BreadcrumbParamScalar|array<array-key, mixed>
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class BreadcrumbContext
{
    /** @var array<string, BreadcrumbParamValue> */
    private array $params;

    /**
     * @param array<string, mixed> $params
     *
     * @throws UnsupportedBreadcrumbParameterTypeException
     */
    public function __construct(
        private string $name,
        array $params = [],
    ) {
        $this->params = $this->normalizeMap($params);
    }

    /**
     * Get the breadcrumb definition name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, BreadcrumbParamValue>
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Determine whether a parameter exists in this context.
     */
    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Get a normalized parameter value.
     *
     * @template TDefault
     * @param  TDefault                            $default
     * @throws MissingBreadcrumbParameterException
     * @return BreadcrumbParamValue|TDefault
     */
    public function param(string $key, mixed $default = null): mixed
    {
        if ($this->hasParam($key)) {
            return $this->params[$key];
        }

        if (func_num_args() === 2) {
            return $default;
        }

        throw MissingBreadcrumbParameterException::forContext($this->name, $key);
    }

    /**
     * @param array<string, mixed> $params
     *
     * Create a new context instance with a different name and parameters.
     *
     * @throws UnsupportedBreadcrumbParameterTypeException
     */
    public function withNameAndParams(string $name, array $params = []): self
    {
        return new self($name, $params);
    }

    /**
     * @param  array<string, mixed>                $values
     * @return array<string, BreadcrumbParamValue>
     */
    private function normalizeMap(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $normalized[$key] = $this->normalizeValue((string) $key, $value);
        }

        return $normalized;
    }

    /**
     * @throws UnsupportedBreadcrumbParameterTypeException
     * @return BreadcrumbParamValue
     */
    private function normalizeValue(string $key, mixed $value): mixed
    {
        if (null === $value || is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $nestedKey => $nestedValue) {
                $normalized[$nestedKey] = $this->normalizeValue($key.'.'.$nestedKey, $nestedValue);
            }

            return $normalized;
        }

        throw UnsupportedBreadcrumbParameterTypeException::forContext($this->name, $key, get_debug_type($value));
    }
}
