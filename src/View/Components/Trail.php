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
 * Blade component entry point for rendering the active breadcrumb trail.
 *
 * This component lives at the view boundary of the package. It translates Blade
 * component arguments into a call on {@see BreadcrumbsManager}, letting views
 * request either the breadcrumb trail for the current route or an explicit
 * named definition with ad-hoc parameters.
 *
 * The component itself performs no breadcrumb resolution. It delegates all
 * lookup, parameter normalization, and view selection rules to the manager so
 * Blade usage stays thin and consistent with programmatic rendering.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Trail extends Component
{
    /**
     * Create a new breadcrumb trail component instance.
     *
     * Resolution precedence is deferred to the manager:
     * - `$name === null` means "render the trail for the current route".
     * - `$params` are forwarded as explicit runtime parameters for named or
     * route-derived breadcrumbs.
     * - `$view` overrides the configured breadcrumb view when provided.
     *
     * No work is performed during construction beyond storing these inputs for
     * the eventual render phase.
     *
     * @param BreadcrumbsManager   $manager Breadcrumb orchestration service.
     * @param null|string          $name    Explicit breadcrumb name, or the
     *                                      current route when null.
     * @param array<string, mixed> $params  Runtime parameters forwarded to the
     *                                      manager.
     * @param null|string          $view    Explicit view override, or the
     *                                      configured default when null.
     */
    public function __construct(
        private readonly BreadcrumbsManager $manager,
        public readonly ?string $name = null,
        public readonly array $params = [],
        public readonly ?string $view = null,
    ) {}

    /**
     * Resolve and render the breadcrumb view for this component instance.
     *
     * Rendering is delegated entirely to {@see BreadcrumbsManager}. Any failure
     * to determine the configured view or resolve the requested breadcrumb is
     * surfaced from that layer rather than being handled inside the component.
     *
     * @throws ViewNotConfiguredException
     */
    public function render(): View
    {
        return $this->manager->render($this->name, $this->params, $this->view);
    }
}
