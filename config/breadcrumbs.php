<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default View
    |--------------------------------------------------------------------------
    |
    | View used by Breadcrumbs::render() when no explicit view is passed.
    | This package does not ship default templates; you must provide one.
    |
    */

    'view' => null,

    /*
    |--------------------------------------------------------------------------
    | Definition Classes
    |--------------------------------------------------------------------------
    |
    | Register breadcrumb definitions as class names implementing
    | Cline\Breadcrumbs\Contracts\BreadcrumbDefinition.
    |
    */

    'definitions' => [
        /*
        |--------------------------------------------------------------------------
        | Definition Classes
        |--------------------------------------------------------------------------
        |
        | Add fully-qualified class names for breadcrumb definition classes.
        | Each class should implement the breadcrumb definition contract and
        | register one or more breadcrumb trails for your application.
        |
        */

    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Discovery
    |--------------------------------------------------------------------------
    |
    | Optional definition discovery paths. When enabled, discovered classes
    | are merged with the configured definitions above.
    |
    */

    'discovery' => [
        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Enable or disable automatic discovery of breadcrumb definition
        | classes from the configured paths below.
        |
        */

        'enabled' => false,

        /*
        |--------------------------------------------------------------------------
        | Paths
        |--------------------------------------------------------------------------
        |
        | Directories that will be scanned for breadcrumb definition classes
        | when discovery is enabled.
        |
        */

        'paths' => [
            /*
            |--------------------------------------------------------------------------
            | Breadcrumbs Directory
            |--------------------------------------------------------------------------
            |
            | Default application directory where breadcrumb definition classes
            | are commonly stored.
            |
            */

            app_path('Breadcrumbs'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Classmap Paths
        |--------------------------------------------------------------------------
        |
        | Optional classmap files that return an array of
        | class-string => absolute file path entries. Composer's generated
        | classmap is used by default.
        |
        */

        'classmap_paths' => [
            base_path('vendor/composer/autoload_classmap.php'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback Definition Files
    |--------------------------------------------------------------------------
    |
    | Route-style callback registrations can be auto-loaded from file patterns.
    | Matching files are required during package boot in sorted order.
    |
    */

    'callbacks' => [
        'autoload' => [
            base_path('breadcrumbs/*.php'),
            base_path('modules/*/Breadcrumbs/*.php'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache resolved definition class names for fast boot.
    |
    */

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Enable caching of resolved breadcrumb definition class names to
        | improve bootstrap performance.
        |
        */

        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Cache Path
        |--------------------------------------------------------------------------
        |
        | The absolute file path where the compiled breadcrumb definition
        | cache is written.
        |
        */

        'path' => base_path('bootstrap/cache/breadcrumbs.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Serializers
    |--------------------------------------------------------------------------
    |
    | Register output format serializers used by Breadcrumbs::serialize() and
    | Breadcrumbs::toResponse().
    |
    */

    'serializers' => [
        'trail' => Cline\Breadcrumbs\Serialization\ArraySerializer::class,
        'jsonld' => Cline\Breadcrumbs\Serialization\JsonLdSerializer::class,
    ],
];
