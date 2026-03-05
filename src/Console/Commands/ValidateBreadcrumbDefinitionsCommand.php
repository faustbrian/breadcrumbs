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
 * Validates breadcrumb definitions for missing parents and cycles.
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
     * Execute the command.
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
