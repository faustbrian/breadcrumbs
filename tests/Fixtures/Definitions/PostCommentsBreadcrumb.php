<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Definitions;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class PostCommentsBreadcrumb implements BreadcrumbDefinition
{
    public function name(): string
    {
        return 'post.comments';
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $post = $context->param('post');

        $trail->parent('post.show');
        $trail->push('Comments', '/posts/'.$post['id'].'/comments');
    }
}
