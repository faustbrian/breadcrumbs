<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Core;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Discovery\DefinitionClassResolver;
use Cline\Breadcrumbs\Exceptions\DuplicateBreadcrumbDefinitionException;
use Cline\Breadcrumbs\Exceptions\InvalidBreadcrumbDefinitionException;
use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbDefinitionException;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;

use function is_subclass_of;
use function throw_if;
use function throw_unless;

/**
 * Name-indexed catalog of breadcrumb definition instances.
 *
 * The registry is populated during container construction from the resolved
 * definition class list and then serves as the authoritative lookup source for
 * trail resolution. It centralizes two invariants for the package:
 * definition classes must implement {@see BreadcrumbDefinition}, and each
 * resolved name must be unique across discovered and manually configured
 * definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class DefinitionRegistry
{
    /** @var array<string, BreadcrumbDefinition> */
    private array $definitions = [];

    /**
     * Build the registry from the discovered definition class list.
     *
     * Resolution order is inherited from {@see DefinitionClassResolver}. Each
     * class is instantiated through the container so constructor dependencies
     * and contextual bindings remain active, then keyed by the runtime value of
     * {@see BreadcrumbDefinition::name()}.
     *
     * @throws DuplicateBreadcrumbDefinitionException
     * @throws InvalidBreadcrumbDefinitionException
     */
    public function __construct(Container $container, DefinitionClassResolver $resolver)
    {
        foreach ($resolver->resolve() as $definitionClass) {
            throw_unless(
                is_subclass_of($definitionClass, BreadcrumbDefinition::class),
                InvalidBreadcrumbDefinitionException::forDefinitionClass($definitionClass),
            );

            /** @var BreadcrumbDefinition $definition */
            $definition = $container->make($definitionClass);
            $name = $definition->name();

            throw_if(
                isset($this->definitions[$name]),
                DuplicateBreadcrumbDefinitionException::forName($name, $definitionClass),
            );

            $this->register($definition);
        }
    }

    /**
     * Register a definition while preserving the uniqueness invariant for its name.
     *
     * This method is strict by design: attempting to replace an existing name
     * is treated as a package configuration error because trail resolution must
     * remain deterministic once the registry is assembled.
     *
     * @throws DuplicateBreadcrumbDefinitionException
     */
    public function register(BreadcrumbDefinition $definition): void
    {
        $name = $definition->name();

        throw_if(
            isset($this->definitions[$name]),
            DuplicateBreadcrumbDefinitionException::forName($name, $definition::class),
        );

        $this->definitions[$name] = $definition;
    }

    /**
     * Register or replace a definition without duplicate checks.
     *
     * This is the explicit escape hatch for callers that intentionally want
     * last-write-wins behavior. Use it only when overwriting an existing
     * definition is part of the desired runtime customization strategy.
     */
    public function upsert(BreadcrumbDefinition $definition): void
    {
        $this->definitions[$definition->name()] = $definition;
    }

    /**
     * Resolve a definition by its public breadcrumb name.
     *
     * Missing names are treated as hard failures because the resolver cannot
     * continue building a trail once a referenced parent or root definition is
     * absent.
     *
     * @throws MissingBreadcrumbDefinitionException
     */
    public function get(string $name): BreadcrumbDefinition
    {
        throw_unless(isset($this->definitions[$name]), MissingBreadcrumbDefinitionException::forName($name));

        return $this->definitions[$name];
    }

    /**
     * Determine whether a definition exists without triggering exception flow.
     */
    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    /**
     * Return the entire registry keyed by breadcrumb name.
     *
     * The returned array exposes the live in-memory registry state, which makes
     * it suitable for diagnostics and cache warm-up style workflows.
     *
     * @return array<string, BreadcrumbDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }
}
