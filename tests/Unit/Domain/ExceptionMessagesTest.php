<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Exceptions\DuplicateBreadcrumbDefinitionException;
use Cline\Breadcrumbs\Exceptions\InvalidBreadcrumbDefinitionException;
use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbDefinitionException;
use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbParameterException;
use Cline\Breadcrumbs\Exceptions\MissingCurrentRouteNameException;
use Cline\Breadcrumbs\Exceptions\ViewNotConfiguredException;

it('exposes stable exception messages', function (): void {
    expect(
        DuplicateBreadcrumbDefinitionException::forName('home', 'App\\Home')->getMessage(),
    )
        ->toContain('Breadcrumb [home] is already registered.')
        ->and(
            InvalidBreadcrumbDefinitionException::forDefinitionClass('Foo')->getMessage(),
        )
        ->toContain('must implement BreadcrumbDefinition')
        ->and(
            MissingBreadcrumbDefinitionException::forName('missing')->getMessage(),
        )
        ->toContain('[missing]')
        ->and(
            MissingBreadcrumbParameterException::forContext('home', 'id')->getMessage(),
        )
        ->toContain('[id]')
        ->and(
            MissingCurrentRouteNameException::forCurrentRoute()->getMessage(),
        )
        ->toContain('Current route is not available')
        ->and(
            ViewNotConfiguredException::forBreadcrumbsView()->getMessage(),
        )
        ->toContain('Breadcrumb view is not configured');
});
