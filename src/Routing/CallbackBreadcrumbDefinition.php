<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Routing;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;
use Cline\Breadcrumbs\Support\BreadcrumbParamResolver;
use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

use function array_key_exists;
use function is_a;

/**
 * Runtime breadcrumb definition backed by a user-supplied closure.
 *
 * This adapter lets the package keep a formal `BreadcrumbDefinition` contract
 * internally while exposing a lightweight closure API to consumers. At build
 * time it reflects the callback signature, resolves the first parameter as
 * either the raw `TrailBuilder` or the route-style `BreadcrumbTrail`, then
 * resolves remaining parameters from the breadcrumb context in a predictable
 * precedence order.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class CallbackBreadcrumbDefinition implements BreadcrumbDefinition
{
    public function __construct(
        private string $breadcrumbName,
        private Closure $callback,
    ) {}

    public function name(): string
    {
        return $this->breadcrumbName;
    }

    /**
     * Build the breadcrumb trail by invoking the registered callback.
     *
     * Parameter resolution happens on each invocation rather than at
     * registration time so callbacks see the live runtime context. The first
     * parameter is special-cased for builder injection; subsequent parameters
     * are resolved from the context, default values, or null when no value can
     * be supplied.
     */
    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $callbackReflection = new ReflectionFunction($this->callback);
        $routeStyleTrail = new BreadcrumbTrail($trail, $context);
        $arguments = [];

        foreach ($callbackReflection->getParameters() as $index => $parameter) {
            if ($index === 0) {
                $arguments[] = $this->resolveFirstParameter($parameter, $trail, $routeStyleTrail);

                continue;
            }

            $arguments[] = $this->resolveParameter($parameter, $context);
        }

        ($this->callback)(...$arguments);
    }

    /**
     * Resolve the callback's first parameter to the preferred builder type.
     *
     * A first parameter typed as `TrailBuilder` receives the lower-level core
     * builder directly. Any other signature, including an untyped parameter,
     * receives the route-style `BreadcrumbTrail` wrapper for the package's
     * fluent public API.
     */
    private function resolveFirstParameter(
        ReflectionParameter $parameter,
        TrailBuilder $trail,
        BreadcrumbTrail $routeStyleTrail,
    ): mixed {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            if (is_a($typeName, TrailBuilder::class, true)) {
                return $trail;
            }
        }

        return $routeStyleTrail;
    }

    /**
     * Resolve a non-builder callback parameter from the current breadcrumb context.
     *
     * Resolution order is:
     * 1. Inject the full `BreadcrumbContext` when explicitly type-hinted.
     * 2. Resolve a named context parameter.
     * 3. Rehydrate a model via `BreadcrumbParamResolver` when the parameter is
     *    type-hinted to a non-builtin class and a matching context value exists.
     * 4. Fall back to the PHP default value.
     * 5. Return the raw named parameter from the full params array when present.
     * 6. Return `null` when nothing matches.
     *
     * This ordering preserves ergonomic callback signatures while keeping model
     * resolution explicit and deterministic.
     */
    private function resolveParameter(ReflectionParameter $parameter, BreadcrumbContext $context): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && is_a($type->getName(), BreadcrumbContext::class, true)) {
            return $context;
        }

        $name = $parameter->getName();

        if ($context->hasParam($name)) {
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                return BreadcrumbParamResolver::resolveModel($context, $name, $type->getName());
            }

            return $context->param($name);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $params = $context->params();

        if (array_key_exists($name, $params)) {
            return $params[$name];
        }

        return null;
    }
}
