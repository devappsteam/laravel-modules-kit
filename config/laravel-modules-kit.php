<?php

return [
    'enabled' => env('LARAVEL_MODULES_KIT_ENABLED', true),

    'namespace' => env('LARAVEL_MODULES_KIT_NAMESPACE', 'App\\Modules'),

    'paths' => [
        'modules' => env('LARAVEL_MODULES_KIT_PATH', 'app/Modules'),
        'views_overrides' => env('LARAVEL_MODULES_KIT_VIEWS_PATH', 'views/modules'),
    ],

    'runtime' => [
        'register_module_providers' => true,
        'load_configs' => true,
        'load_routes' => true,
        'load_migrations' => true,
        'load_views' => true,
        'publish_module_configs' => true,
    ],

    'web' => [
        'middleware' => ['web'],
    ],

    'api' => [
        'prefix' => env('LARAVEL_MODULES_KIT_API_PREFIX', 'api/v1'),
        'middleware' => ['api'],
    ],

    'generator' => [
        'default_type' => 'api',
        'stubs_path' => env('LARAVEL_MODULES_KIT_STUBS_PATH', 'stubs/vendor/laravel-modules-kit'),
    ],
];
