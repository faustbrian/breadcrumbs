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
 * Serializes a breadcrumb trail into schema.org `BreadcrumbList` JSON-LD.
 *
 * This serializer adapts the internal trail structure to a search-engine
 * friendly format without mutating the underlying trail. Positions are emitted
 * in build order starting at one, and the terminal breadcrumb omits `item`
 * when no URL is available so the payload stays valid for current-page entries.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class JsonLdSerializer implements BreadcrumbSerializer
{
    /**
     * Convert the trail into a schema.org breadcrumb list payload.
     *
     * Each item is mapped to a `ListItem` entry. URLs are included only when
     * present because schema.org allows the current page breadcrumb to omit the
     * target item reference.
     *
     * @return array<string, mixed>
     */
    public function serialize(BreadcrumbTrail $trail): array
    {
        $items = [];

        foreach ($trail->items() as $index => $breadcrumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb->label(),
            ];

            if ($breadcrumb->url() !== null) {
                $item['item'] = $breadcrumb->url();
            }

            $items[] = $item;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}
