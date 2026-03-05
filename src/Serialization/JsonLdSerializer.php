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
 * Serializes a breadcrumb trail into schema.org JSON-LD.
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class JsonLdSerializer implements BreadcrumbSerializer
{
    /**
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
