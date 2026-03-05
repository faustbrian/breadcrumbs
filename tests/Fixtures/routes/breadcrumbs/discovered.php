<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Cline\Breadcrumbs\Routing\BreadcrumbTrail;

Breadcrumbs::for('auto.discovered', function (BreadcrumbTrail $trail): void {
    $trail->push('Auto Discovered', '/auto-discovered');
});
