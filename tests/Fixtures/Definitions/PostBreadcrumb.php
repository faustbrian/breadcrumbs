<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Definitions;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Contracts\ParentAwareBreadcrumbDefinition;
use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

use function is_array;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class PostBreadcrumb implements BreadcrumbDefinition, ParentAwareBreadcrumbDefinition
{
    public function name(): string
    {
        return 'post.show';
    }

    /**
     * @return list<string>
     */
    public function parents(): array
    {
        return ['home'];
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $post = $context->param('post');

        $trail->parent('home');

        if (is_array($post)) {
            $trail->push($post['title'], '/posts/'.$post['id']);

            return;
        }

        $trail->push('Post '.$post, '/posts/'.$post);
    }
}
