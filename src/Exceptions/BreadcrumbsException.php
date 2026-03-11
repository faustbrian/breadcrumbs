<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

use Throwable;

/**
 * Marker contract for failures raised from the breadcrumbs package.
 *
 * This interface gives callers a stable way to distinguish breadcrumb domain
 * errors from generic framework or PHP exceptions. All exceptions that
 * represent definition discovery, trail resolution, serializer resolution, and
 * cache persistence failures are expected to implement this contract, either
 * directly or through {@see BaseException}.
 *
 * Consumers can safely catch this interface around package entry points such as
 * trail resolution, response serialization, console discovery commands, or
 * route-based rendering when they want to recover from breadcrumb-specific
 * misconfiguration without masking unrelated runtime failures.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface BreadcrumbsException extends Throwable {}
