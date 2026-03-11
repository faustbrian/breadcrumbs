<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Exceptions;

/**
 * Thrown when the definition cache cannot prepare its target directory on disk.
 *
 * Discovery commands may persist resolved breadcrumb definition class names to
 * a PHP cache file so later boots can skip filesystem scanning. Before that
 * file can be written, {@see \Cline\Breadcrumbs\Discovery\DefinitionCache}
 * ensures that the parent directory exists. This exception is raised when that
 * directory cannot be created, usually because of permissions, invalid paths,
 * or other filesystem constraints.
 *
 * The cache write is aborted before any file contents are written, leaving the
 * previous cache file state untouched apart from the failed directory creation
 * attempt.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnableToCreateBreadcrumbCacheDirectoryException extends BaseException
{
    /**
     * Create an exception for cache writes whose parent directory cannot be created.
     */
    public static function forPath(): self
    {
        return new self('Unable to create breadcrumb cache directory.');
    }
}
