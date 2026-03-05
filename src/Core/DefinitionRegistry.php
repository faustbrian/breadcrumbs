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
 * Name-indexed registry of resolved breadcrumb definition instances.
 *
 * The registry is fully built during construction and then used as a
 * read-only lookup for trail resolution.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class DefinitionRegistry
{
    /** @var array<string, BreadcrumbDefinition> */
    private array $definitions = [];

    /**
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
     * Register a breadcrumb definition instance.
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
     * Register or replace a breadcrumb definition instance by name.
     */
    public function upsert(BreadcrumbDefinition $definition): void
    {
        $this->definitions[$definition->name()] = $definition;
    }

    /**
     * Get a breadcrumb definition by name.
     *
     * @throws MissingBreadcrumbDefinitionException
     */
    public function get(string $name): BreadcrumbDefinition
    {
        throw_unless(isset($this->definitions[$name]), MissingBreadcrumbDefinitionException::forName($name));

        return $this->definitions[$name];
    }

    /**
     * Determine whether a breadcrumb definition is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    /**
     * @return array<string, BreadcrumbDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }
}
