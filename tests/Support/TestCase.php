<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support;

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Cline\Breadcrumbs\ServiceProvider;
use Illuminate\Config\Repository;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

use function base_path;
use function is_file;
use function unlink;

/**
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
abstract class TestCase extends TestbenchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $cachePath = base_path('bootstrap/cache/breadcrumbs-test.php');

        if (!is_file($cachePath)) {
            return;
        }

        unlink($cachePath);
    }

    protected function defineEnvironment($app): void
    {
        $app->make(Repository::class)->set('view.paths', [__DIR__.'/../resources/views']);
        $app->make(Repository::class)->set('breadcrumbs.view', 'breadcrumbs');
        $app->make(Repository::class)->set('breadcrumbs.definitions', []);
        $app->make(Repository::class)->set('breadcrumbs.cache.path', base_path('bootstrap/cache/breadcrumbs-test.php'));
    }

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Breadcrumbs' => Breadcrumbs::class];
    }
}
