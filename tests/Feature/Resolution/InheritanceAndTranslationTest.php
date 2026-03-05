<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Definitions\HomeBreadcrumb;
use Tests\Fixtures\Definitions\LocalizedBreadcrumb;
use Tests\Fixtures\Definitions\PostBreadcrumb;
use Tests\Fixtures\Definitions\PostCommentsBreadcrumb;

it('inherits parent params when none are provided explicitly', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, PostBreadcrumb::class, PostCommentsBreadcrumb::class]);

    $trail = Breadcrumbs::trail('post.comments', ['post' => ['id' => 7, 'title' => 'Post 7']]);

    expect($trail->labels())->toBe(['Home', 'Post 7', 'Comments']);
});

it('supports translated labels from the trail builder', function (): void {
    resolve(Translator::class)->addLines([
        'breadcrumbs.localized_title' => 'Localized Label',
    ], app()->getLocale());

    Config::set('breadcrumbs.definitions', [LocalizedBreadcrumb::class]);

    $trail = Breadcrumbs::trail('localized');

    expect($trail->items()[0]->label())->toBe('Localized Label');
});
