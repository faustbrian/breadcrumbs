<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Console\Commands;

use Cline\Breadcrumbs\Validation\DefinitionValidator;
use Illuminate\Console\Command;
use Override;

use function implode;

/**
 * Run structural validation against the registered breadcrumb graph.
 *
 * This command gives package maintainers a deployment and CI entry point for
 * catching invalid parent references and cyclical parent chains before those
 * definitions are exercised by a request. It delegates all graph analysis to
 * the validator and focuses on translating the result into operator-facing
 * console output.
 *
 * Missing parents and cycles are emitted individually so multiple structural
 * problems can be diagnosed in a single run. Validation exceptions are not
 * swallowed because they indicate bugs in the registry or validator itself.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ValidateBreadcrumbDefinitionsCommand extends Command
{
    #[Override()]
    protected $signature = 'breadcrumbs:validate';

    #[Override()]
    protected $description = 'Validate known breadcrumb definitions for missing parents and cycles';

    public function __construct(
        private readonly DefinitionValidator $validator,
    ) {
        parent::__construct();
    }

    /**
     * Validate the current definition graph and print all discovered problems.
     *
     * Returns `SUCCESS` only when the validator reports a fully consistent
     * graph. Each missing parent edge and cycle path is written to the error
     * output before the command exits with `FAILURE`.
     *
     * @return self::FAILURE|self::SUCCESS
     */
    public function handle(): int
    {
        $result = $this->validator->validate();

        if ($result->isValid()) {
            $this->info('Breadcrumb definitions are valid.');

            return self::SUCCESS;
        }

        foreach ($result->missingParents() as $missingParent) {
            $this->error('Missing parent: '.$missingParent);
        }

        foreach ($result->cycles() as $cycle) {
            $this->error('Cycle: '.implode(' -> ', $cycle));
        }

        return self::FAILURE;
    }
}
