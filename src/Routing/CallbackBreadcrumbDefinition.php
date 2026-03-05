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
use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

use function array_key_exists;
use function is_a;

/**
 * Runtime breadcrumb definition created from a closure callback.
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

    private function resolveParameter(ReflectionParameter $parameter, BreadcrumbContext $context): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && is_a($type->getName(), BreadcrumbContext::class, true)) {
            return $context;
        }

        $name = $parameter->getName();

        if ($context->hasParam($name)) {
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
