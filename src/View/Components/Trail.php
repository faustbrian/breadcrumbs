<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\View\Components;

use Cline\Breadcrumbs\Core\BreadcrumbsManager;
use Cline\Breadcrumbs\Exceptions\ViewNotConfiguredException;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Blade component for rendering a breadcrumb trail.
 * @author Brian Faust <brian@cline.sh>
 */
final class Trail extends Component
{
    /**
     * @param BreadcrumbsManager   $manager Breadcrumb manager facade service.
     * @param null|string          $name    Explicit breadcrumb name, or current route when null.
     * @param array<string, mixed> $params
     * @param null|string          $view    Override view name, or configured default when null.
     */
    public function __construct(
        private readonly BreadcrumbsManager $manager,
        public readonly ?string $name = null,
        public readonly array $params = [],
        public readonly ?string $view = null,
    ) {}

    /**
     * Render the configured breadcrumb component view.
     *
     * @throws ViewNotConfiguredException
     */
    public function render(): View
    {
        return $this->manager->render($this->name, $this->params, $this->view);
    }
}
