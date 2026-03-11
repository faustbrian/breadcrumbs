<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Serialization;

use Cline\Breadcrumbs\Contracts\BreadcrumbSerializer;
use Cline\Breadcrumbs\Core\BreadcrumbTrail;
use Illuminate\Container\Attributes\Singleton;

/**
 * Serializes a built breadcrumb trail into the package's canonical array payload.
 *
 * This format is the lowest-friction representation exposed by the package and
 * acts as the baseline for JSON responses, testing, and custom presentation
 * layers. The serializer intentionally preserves the trail's existing item
 * ordering and payload shape instead of transforming or enriching it.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class ArraySerializer implements BreadcrumbSerializer
{
    /**
     * Return the trail as its native list of breadcrumb item payloads.
     *
     * No additional normalization happens here; any invariants around item
     * labels, URLs, metadata, or HTML attributes must already have been
     * enforced during trail construction.
     *
     * @return list<array<string, mixed>>
     */
    public function serialize(BreadcrumbTrail $trail): array
    {
        return $trail->toArray();
    }
}
