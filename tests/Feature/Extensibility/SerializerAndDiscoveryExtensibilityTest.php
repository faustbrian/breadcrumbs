<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Definitions\HomeBreadcrumb;
use Tests\Fixtures\Definitions\PostBreadcrumb;
use Tests\Fixtures\Serialization\LabelsSerializer;

it('uses configured custom serializers for toResponse formats', function (): void {
    Config::set('breadcrumbs.definitions', [HomeBreadcrumb::class, PostBreadcrumb::class]);
    Config::set('breadcrumbs.serializers', [
        'labels' => LabelsSerializer::class,
    ]);

    $response = Breadcrumbs::toResponse('post.show', ['post' => ['id' => 22, 'title' => 'Post 22']], 'labels');

    expect($response->getData(true))->toBe(['Home', 'Post 22']);
});

it('discovers definitions from configured classmap files', function (): void {
    $tmpDir = base_path('bootstrap/cache/breadcrumbs-discovery-tests');

    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0o755, true);
    }

    $suffix = uniqid('ext', true);
    $className = 'ExternalBreadcrumb'.str_replace('.', '', $suffix);
    $breadcrumbName = 'external.home.'.$suffix;
    $fqcn = 'Tmp\\Breadcrumbs\\'.$className;

    $classFile = $tmpDir.'/'.$className.'.php';
    $classmapFile = $tmpDir.'/classmap.php';

    $classCode = sprintf(
        <<<'PHP'
<?php declare(strict_types=1);

namespace Tmp\Breadcrumbs;

use Cline\Breadcrumbs\Contracts\BreadcrumbDefinition;
use Cline\Breadcrumbs\Core\BreadcrumbContext;
use Cline\Breadcrumbs\Core\TrailBuilder;

final class %s implements BreadcrumbDefinition
{
    public function name(): string
    {
        return '%s';
    }

    public function build(TrailBuilder $trail, BreadcrumbContext $context): void
    {
        $trail->push('External Home');
    }
}
PHP,
        $className,
        $breadcrumbName,
    );

    file_put_contents($classFile, $classCode);

    file_put_contents(
        $classmapFile,
        "<?php\n\nreturn ".var_export([$fqcn => $classFile], true).";\n",
    );

    Config::set('breadcrumbs.definitions', []);
    Config::set('breadcrumbs.discovery.enabled', true);
    Config::set('breadcrumbs.discovery.paths', [$tmpDir]);
    Config::set('breadcrumbs.discovery.classmap_paths', [$classmapFile]);

    $trail = Breadcrumbs::trail($breadcrumbName);

    expect($trail->labels())->toBe(['External Home']);
});
