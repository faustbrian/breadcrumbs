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
 * Immutable runtime context supplied to breadcrumb resolution.
 *
 * The context carries the target definition name together with normalized
 * parameters that definitions can safely read during build. Normalization is
 * performed up front so every consumer sees the same scalar, enum, stringable,
 * and nested-array representations regardless of the original caller input.
 *
 * Unsupported parameter types are rejected at construction time instead of
 * leaking deeper into trail building. That keeps failure localized to context
 * creation and guarantees `param()` only ever exposes the supported normalized
 * value shapes declared below.
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
     * Create a context for one definition resolution request.
     *
     * Parameters are normalized immediately so invalid value types fail before
     * any definition code runs. Nested arrays are normalized recursively using
     * dotted keys in exception messages to identify the failing path.
     *
     * @param array<string, mixed> $params
     *
     * @throws UnsupportedBreadcrumbParameterTypeException When any parameter
     *                                                     cannot be reduced to
     *                                                     a supported scalar or
     *                                                     nested array value.
     */
    public function __construct(
        private string $name,
        array $params = [],
    ) {
        $this->params = $this->normalizeMap($params);
    }

    /**
     * Return the definition name this context should resolve.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return the fully normalized parameter map for this context.
     *
     * Callers receive the exact values that `param()` would expose, which makes
     * this suitable for forwarding the whole context into nested resolution or
     * debugging serialization.
     *
     * @return array<string, BreadcrumbParamValue>
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Determine whether a parameter key was supplied after normalization.
     *
     * This distinguishes a missing key from a key explicitly set to `null`,
     * which matters for definitions that treat null as meaningful state.
     */
    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Read one normalized parameter value from the context.
     *
     * If the key exists, the stored normalized value is returned even when that
     * value is `null`. If the key does not exist, the provided default is used
     * when supplied; otherwise a missing-parameter exception is raised so
     * callers can fail loudly for required inputs.
     *
     * @template TDefault
     * @param  TDefault                            $default
     * @throws MissingBreadcrumbParameterException When the key is absent and no
     *                                             default argument was passed.
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
     * Create a new normalized context for another definition request.
     *
     * This is a convenience for parent or sibling resolution flows that need a
     * fresh immutable context rather than mutating the current instance in
     * place.
     *
     * @param array<string, mixed> $params
     *
     * @throws UnsupportedBreadcrumbParameterTypeException When the replacement
     *                                                     parameters include an
     *                                                     unsupported value.
     */
    public function withNameAndParams(string $name, array $params = []): self
    {
        return new self($name, $params);
    }

    /**
     * Normalize an associative parameter map one key at a time.
     *
     * Keys are string-cast to guarantee consistent lookup semantics even when
     * callers provide integer-like array keys.
     *
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
     * Normalize one parameter value into the supported transport shape.
     *
     * Resolution order is deliberate: scalars remain unchanged, backed enums
     * become their backing values, pure enums become case names, stringables
     * are string-cast, and arrays recurse depth-first. Any remaining type is
     * rejected with the current dotted key path in the exception payload.
     *
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
