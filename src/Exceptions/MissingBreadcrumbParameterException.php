<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

use function sprintf;

/**
 * Thrown when a breadcrumb definition asks for context data that was never provided.
 *
 * {@see \Cline\Breadcrumbs\Core\BreadcrumbContext} normalizes route and manual
 * parameters before a definition reads them. Definitions may then require
 * specific keys to build labels, URLs, or metadata. When a key is absent and no
 * default value was supplied to the context lookup, this exception fails fast
 * instead of allowing downstream null handling or malformed trail items.
 *
 * The exception identifies the breadcrumb currently being resolved, which makes
 * it easier to distinguish missing caller input from serializer or rendering
 * failures later in the lifecycle.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingBreadcrumbParameterException extends BaseException
{
    /**
     * Create an exception for a required context parameter that is missing.
     *
     * @param string $name  Breadcrumb definition name.
     * @param string $param Missing required parameter key.
     */
    public static function forContext(string $name, string $param): self
    {
        return new self(sprintf('Breadcrumb [%s] requires parameter [%s].', $name, $param));
    }
}
