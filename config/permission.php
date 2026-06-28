<?php



return [



    'enabled' => env('MCA_PERMISSION_ENABLED', true),



    /*

    |--------------------------------------------------------------------------

    | Izin modu — kurulum: php artisan mca:permission:install

    |--------------------------------------------------------------------------

    | basic : rol + izin

    | user  : rol + izin, kullaniciya ek izin

    | full  : rol + izin, departman + izin, kullanici + izin

    */

    'mode' => env('MCA_PERMISSION_MODE', 'basic'),

    /*
    |--------------------------------------------------------------------------
    | UI locale (null = app locale)
    |--------------------------------------------------------------------------
    */
    'locale' => env('MCA_PERMISSION_LOCALE'),

    /*

    |--------------------------------------------------------------------------

    | Kullanici modeli

    |--------------------------------------------------------------------------

    */

    'user_model' => env('MCA_PERMISSION_USER_MODEL', App\Models\User::class),

    'user_role_column' => env('MCA_PERMISSION_USER_ROLE_COLUMN', 'role_id'),

    'user' => [
        'default_role' => env('MCA_PERMISSION_USER_DEFAULT_ROLE', 'editor'),
        'default_role_id' => env('MCA_PERMISSION_USER_DEFAULT_ROLE_ID'),
        'exclusive_column' => env('MCA_PERMISSION_USER_EXCLUSIVE_COLUMN', 'mca_permission_exclusive'),
    ],



    /*

    |--------------------------------------------------------------------------

    | Departman (full mod)

    |--------------------------------------------------------------------------

    */

    'department' => [

        'model' => env('MCA_PERMISSION_DEPARTMENT_MODEL'),

        'table' => env('MCA_PERMISSION_DEPARTMENT_TABLE', 'departments'),

        'user_column' => env('MCA_PERMISSION_DEPARTMENT_USER_COLUMN', 'department_id'),

        'route_key' => env('MCA_PERMISSION_DEPARTMENT_ROUTE_KEY', 'id'),

        'exclusive_column' => env('MCA_PERMISSION_DEPARTMENT_EXCLUSIVE_COLUMN', 'mca_permission_exclusive'),

    ],



    /*

    |--------------------------------------------------------------------------

    | View ozellestirme

    |--------------------------------------------------------------------------

    */

    'views' => [

        'namespace' => 'mca-permission',

        'layout' => 'mca-permission::layouts.app',

    ],



    /*

    |--------------------------------------------------------------------------

    | UI — on ekli CSS siniflari (cakisma onleme)

    |--------------------------------------------------------------------------

    */

    'ui' => [

        'enabled' => env('MCA_PERMISSION_UI_ENABLED', true),

        'class_prefix' => 'mca-perm',

        'title' => env('MCA_PERMISSION_UI_TITLE'),

        'assets' => [

            'ui' => 'vendor/mca-permission/mca-ui.css',

            'ui_js' => 'vendor/mca-permission/mca-ui.js',

            'css' => 'vendor/mca-permission/mca-permission.css',

            'js' => 'vendor/mca-permission/mca-permission.js',

        ],

    ],



    /*

    |--------------------------------------------------------------------------

    | Controller siniflari

    |--------------------------------------------------------------------------

    */

    'controllers' => [

        'web' => [

            'permission' => Mca\Permission\Http\Controllers\Web\PermissionController::class,

            'scanner' => Mca\Permission\Http\Controllers\Web\PermissionScannerController::class,

            'role' => Mca\Permission\Http\Controllers\Web\RoleController::class,

            'role_permission' => Mca\Permission\Http\Controllers\Web\RolePermissionController::class,

            'user' => Mca\Permission\Http\Controllers\Web\UserController::class,
            'user_permission' => Mca\Permission\Http\Controllers\Web\UserPermissionController::class,

            'department' => Mca\Permission\Http\Controllers\Web\DepartmentController::class,
            'department_permission' => Mca\Permission\Http\Controllers\Web\DepartmentPermissionController::class,

        ],

        'api' => [

            'permission' => Mca\Permission\Http\Controllers\Api\PermissionApiController::class,

        ],

    ],



    /*

    |--------------------------------------------------------------------------

    | Route ayarlari

    |--------------------------------------------------------------------------

    */

    'routes' => [

        'load_package_routes' => env('MCA_PERMISSION_LOAD_ROUTES', true),

        'name_prefix' => 'mca.permission.',

        'web' => [

            'enabled' => true,

            'prefix' => 'mca/permission',

            'middleware' => ['web', 'auth', 'mca.permission.root'],

        ],

        'api' => [

            'enabled' => true,

            'prefix' => 'mca/permission/api',

            'middleware' => ['web', 'auth', 'mca.permission.root'],

        ],

    ],



    'middleware_alias' => 'mca.permission',

    'root_middleware_alias' => 'mca.permission.root',



    'scan' => [

        'segments' => [

            [
                'folder' => 'Panel',
                'path' => 'Http/Controllers/Panel',
                'namespace' => 'App\\Http\\Controllers\\Panel',
            ],

            [
                'folder' => 'Api',
                'path' => 'Http/Controllers/Api',
                'namespace' => 'App\\Http\\Controllers\\Api',
            ],

        ],

        'sync_segments_on_boot' => env('MCA_PERMISSION_SYNC_SCAN_SEGMENTS', true),

        'controllers_namespace' => 'App\\Http\\Controllers',

        'base_controller' => App\Http\Controllers\Controller::class,

    ],



    'root_only_controllers' => [

        'RolesController',

        'RolePermissionsController',

        'PermissionsController',

        'PermissionScannerController',

        'PermissionApiController',

        'RoleController',

        'RolePermissionController',

        'PermissionController',

        'UserPermissionController',

        'UserController',

        'DepartmentPermissionController',

        'DepartmentController',

    ],



    'cache_key' => 'mca.permission.version',



    'labels' => [

        'folders' => [

            'Panel' => 'Panel',

            'Api' => 'API',

        ],

        'controllers' => [

            'DashboardController' => 'Gösterge paneli',

            'PermissionApiController' => 'İzin API',

            'PermissionController' => 'İzin listesi',

            'PermissionScannerController' => 'İzin tarayıcı',

            'RoleController' => 'Rol yönetimi',

            'RolePermissionController' => 'Rol izin ataması',

            'UserPermissionController' => 'Kullanıcı izin ataması',

            'UserController' => 'Kullanıcı yönetimi',

            'DepartmentPermissionController' => 'Departman izin ataması',

            'DepartmentController' => 'Departman yönetimi',

        ],

        'methods' => [],

    ],



];


