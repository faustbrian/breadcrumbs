<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use RuntimeException;

/**
 * Shared base class for package-level runtime failures.
 *
 * Breadcrumb exceptions inherit from this type so consumers can rely on a
 * consistent exception hierarchy and Ignition solution payload. Concrete
 * exceptions are expected to communicate the precise domain failure while this
 * base class centralizes developer-facing remediation guidance.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class BaseException extends RuntimeException implements BreadcrumbsException, ProvidesSolution
{
    /**
     * Provide a generic Ignition solution payload for breadcrumb exceptions.
     *
     * Concrete exceptions do not currently customize solution text, so the
     * current exception message becomes the primary failure detail and the
     * package documentation link acts as the next diagnostic step.
     */
    public function getSolution(): Solution
    {
        /** @var BaseSolution $solution */
        $solution = BaseSolution::create('Review breadcrumb definition and configuration.');

        return $solution
            ->setSolutionDescription('Exception: '.$this->getMessage())
            ->setDocumentationLinks([
                'Package documentation' => 'https://github.com/cline/breadcrumbs',
            ]);
    }
}
