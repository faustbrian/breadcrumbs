<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Feature\Registration;

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Config\Repository;
use Override;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class CallbackFileAutoDiscoveryTest extends TestCase
{
    public function test_it_auto_loads_callback_breadcrumb_definition_files(): void
    {
        $trail = Breadcrumbs::trail('auto.discovered');

        $this->assertCount(1, $trail->items());
        $this->assertSame('Auto Discovered', $trail->items()[0]->label());
        $this->assertSame('/auto-discovered', $trail->items()[0]->url());
    }

    #[Override()]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->make(Repository::class)->set(
            'breadcrumbs.callbacks.autoload',
            [__DIR__.'/../../Fixtures/breadcrumbs/*.php'],
        );
    }
}
