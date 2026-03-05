<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Validation;

/**
 * Immutable validation result for breadcrumb definition checks.
 *
 * @phpstan-type MissingParentEdge string
 * @phpstan-type BreadcrumbCycle list<string>
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class DefinitionValidationResult
{
    /**
     * @param list<MissingParentEdge> $missingParents
     * @param list<BreadcrumbCycle>   $cycles
     */
    public function __construct(
        private array $missingParents,
        private array $cycles,
    ) {}

    /**
     * @return list<MissingParentEdge>
     */
    public function missingParents(): array
    {
        return $this->missingParents;
    }

    /**
     * @return list<BreadcrumbCycle>
     */
    public function cycles(): array
    {
        return $this->cycles;
    }

    /**
     * Determine whether the validation result has no issues.
     */
    public function isValid(): bool
    {
        return $this->missingParents === [] && $this->cycles === [];
    }
}
