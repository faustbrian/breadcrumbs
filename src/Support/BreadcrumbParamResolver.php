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

/**
 * Shared parameter normalization helpers for route and context usage.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class BreadcrumbParamResolver
{
    /**
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

            /** @var mixed $parameter */
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

            if (self::isSupportedScalar($parameter)) {
                $parameters[$parameterName] = $parameter;
            }
        }

        return $parameters;
    }

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
        $model = app($modelClass);
        $resolved = $model->resolveRouteBinding($value, $model->getRouteKeyName());

        return $resolved ?? $value;
    }

    /**
     * @phpstan-assert-if-true bool|float|int|string $value
     */
    private static function isSupportedScalar(mixed $value): bool
    {
        return is_bool($value) || is_float($value) || is_int($value) || is_string($value);
    }
}
