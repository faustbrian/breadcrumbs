<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Support;

use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Route;

use function in_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function is_subclass_of;
use function resolve;

/**
 * Normalizes breadcrumb parameters as they cross routing and definition
 * boundaries.
 *
 * This helper sits between Laravel's route system, the breadcrumb execution
 * context, and definition callbacks that expect predictable scalar input. It
 * strips route parameters down to values that can be safely persisted into
 * breadcrumb context state or passed back through model resolution later in the
 * lifecycle.
 *
 * The resolver intentionally prefers stable route-key data over arbitrary
 * object graphs. Values that cannot be represented as supported scalars are
 * discarded rather than coerced, which keeps downstream breadcrumb resolution
 * deterministic and avoids leaking transport-specific objects into view logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class BreadcrumbParamResolver
{
    /**
     * Extract supported route parameters for breadcrumb context hydration.
     *
     * Parameters are traversed in the order Laravel reports them for the active
     * route. Excluded names are skipped first. Routable objects are reduced to
     * their route keys, while plain scalar values are copied as-is. Unsupported
     * values, nulls, and routable objects that expose non-scalar route keys are
     * omitted entirely so callers never receive partially normalized data.
     *
     * This method has no side effects beyond reading the route state. The
     * returned array is suitable for storing in {@see BreadcrumbContext} or for
     * forwarding to breadcrumb definitions that expect request-derived
     * parameters.
     *
     * @param  array<int, string>                        $excluded
     * @return array<string, null|bool|float|int|string>
     */
    public static function fromRoute(Route $route, array $excluded = ['locale']): array
    {
        $parameters = [];

        foreach ($route->parameterNames() as $parameterName) {
            /** @var string $parameterName */
            if (in_array($parameterName, $excluded, true)) {
                continue;
            }

            $parameter = $route->parameter($parameterName);

            if ($parameter === null) {
                continue;
            }

            if ($parameter instanceof UrlRoutable) {
                $routeKey = $parameter->getRouteKey();

                if ($routeKey === null || self::isSupportedScalar($routeKey)) {
                    $parameters[$parameterName] = $routeKey;
                }

                continue;
            }

            if (!self::isSupportedScalar($parameter)) {
                continue;
            }

            $parameters[$parameterName] = $parameter;
        }

        return $parameters;
    }

    /**
     * Resolve a context parameter back into an Eloquent model when possible.
     *
     * Breadcrumb definitions often store route keys rather than hydrated model
     * instances so trail construction can remain serializable and predictable.
     * This method performs the inverse step at consumption time: if the target
     * class is an Eloquent model and the stored value is compatible with route
     * binding, the model's `resolveRouteBinding` implementation is invoked.
     *
     * Resolution order is intentionally conservative:
     * 1. Read the raw parameter from the breadcrumb context.
     * 2. Return it unchanged when the target class is not an Eloquent model.
     * 3. Return it unchanged when the stored value is not null or a supported
     * scalar route-key type.
     * 4. Attempt route binding through a resolved model instance.
     * 5. Fall back to the original raw value when binding fails.
     *
     * The only external side effect is resolving the model class from the
     * container and invoking Laravel's route-binding hook. Missing records do
     * not trigger exceptions here; callers receive the original scalar so
     * definitions can decide whether to degrade gracefully or fail later.
     */
    public static function resolveModel(BreadcrumbContext $context, string $param, string $modelClass): mixed
    {
        $value = $context->param($param);

        if (!is_subclass_of($modelClass, 'Illuminate\\Database\\Eloquent\\Model')) {
            return $value;
        }

        if (!($value === null || is_string($value) || is_int($value) || is_float($value) || is_bool($value))) {
            return $value;
        }

        /** @var UrlRoutable $model */
        $model = resolve($modelClass);
        $resolved = $model->resolveRouteBinding($value, $model->getRouteKeyName());

        return $resolved ?? $value;
    }

    /**
     * Determine whether a value can be safely carried through breadcrumb
     * parameter normalization.
     *
     * Breadcrumb context intentionally supports only scalar route-key values so
     * definitions and views do not depend on mutable framework objects.
     *
     * @phpstan-assert-if-true bool|float|int|string $value
     */
    private static function isSupportedScalar(mixed $value): bool
    {
        return is_bool($value) || is_float($value) || is_int($value) || is_string($value);
    }
}
