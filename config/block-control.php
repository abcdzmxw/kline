<?php

return [

    /*
    |--------------------------------------------------------------------------
    | dcat-admin name
    |--------------------------------------------------------------------------
    |
    | This value is the name of dcat-admin, This setting is displayed on the
    | login page.
    |
    */
    'name' => '风控后台',

    /*
    |--------------------------------------------------------------------------
    | dcat-admin logo
    |--------------------------------------------------------------------------
    |
    | The logo of all admin pages. You can also set it as an image by using a
    | `img` tag, eg '<img src="http://logo-url" alt="Admin logo">'.
    |
    */
    'logo' => '<img src="/vendors/dcat-admin/images/logo.png" width="35"> &nbsp;风控',

    /*
    |--------------------------------------------------------------------------
    | dcat-admin mini logo
    |--------------------------------------------------------------------------
    |
    | The logo of all admin pages when the sidebar menu is collapsed. You can
    | also set it as an image by using a `img` tag, eg
    | '<img src="http://logo-url" alt="Admin logo">'.
    |
    */
    'logo-mini' => '<img src="/vendors/dcat-admin/images/logo.png">',

    /*
	 |--------------------------------------------------------------------------
	 | User default avatar
	 |--------------------------------------------------------------------------
	 |
	 | Set a default avatar for newly created users.
	 |
	 */
	'default_avatar' => '@admin/images/default-avatar.jpg',

    /*
    |--------------------------------------------------------------------------
    | dcat-admin route settings
    |--------------------------------------------------------------------------
    |
    | The routing configuration of the admin page, including the path prefix,
    | the controller namespace, and the default middleware. If you want to
    | access through the root path, just set the prefix to empty string.
    |
    */
    'route' => [

        'prefix' => 'risk',

        'namespace' => 'App\\BlockControl\\Controllers',

        'middleware' => ['web', 'admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin install directory
    |--------------------------------------------------------------------------
    |
    | The installation directory of the controller and routing configuration
    | files of the administration page. The default is `app/Admin`, which must
    | be set before running `artisan admin::install` to take effect.
    |
    */
    'directory' => app_path('BlockControl'),

    /*
    |--------------------------------------------------------------------------
    | dcat-admin html title
    |--------------------------------------------------------------------------
    |
    | Html title for all pages.
    |
    */
    'title' => '风控后台',

    /*
    |--------------------------------------------------------------------------
    | Assets hostname
    |--------------------------------------------------------------------------
    |
   */
    'assets_server' => env('ADMIN_ASSETS_SERVER'),

    /*
    |--------------------------------------------------------------------------
    | Access via `https`
    |--------------------------------------------------------------------------
    |
    | If your page is going to be accessed via https, set it to `true`.
    |
    */
    'https' => env('ADMIN_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | dcat-admin auth setting
    |--------------------------------------------------------------------------
    |
    | Authentication settings for all admin pages. Include an authentication
    | guard and a user provider setting of authentication driver.
    |
    | You can specify a controller for `login` `logout` and other auth routes.
    |
    */
    'auth' => [
        'enable' => true,

        'controller' => App\BlockControl\Controllers\AuthController::class,

        'guard' => 'block-control',

        'guards' => [
            'block-control' => [
                'driver'   => 'session',
                'provider' => 'block-control',
            ],
        ],

        'providers' => [
            'block-control' => [
                'driver' => 'eloquent',
                'model'  => \App\Models\BlockControlAdminUsers::class,
            ],
        ],

        // Add "remember me" to login form
        'remember' => true,

        // All method to path like: auth/users/*/edit
        // or specific method to path like: get:auth/users.
        'except' => [
            'auth/login',
            'auth/logout',
            'kline',
            'generateKline',
            'getKlineConfig',
        ],

    ],

    'grid' => [

        /*
        |--------------------------------------------------------------------------
        | The global Grid action display class.
        |--------------------------------------------------------------------------
        */
        'grid_action_class' => Dcat\Admin\Grid\Displayers\DropdownActions::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin helpers setting.
    |--------------------------------------------------------------------------
    */
    'helpers' => [
        'enable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin permission setting
    |--------------------------------------------------------------------------
    |
    | Permission settings for all admin pages.
    |
    */
    'permission' => [
        // Whether enable permission.
        'enable' => true,

        // All method to path like: auth/users/*/edit
        // or specific method to path like: get:auth/users.
        'except' => [
            '/',
            'auth/login',
            'auth/logout',
            'auth/setting',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin menu setting
    |--------------------------------------------------------------------------
    |
    */
    'menu' => [
        'cache' => [
            // enable cache or not
            'enable' => false,
            'store'  => 'file',
        ],

        // Whether enable menu bind to a permission.
        'bind_permission' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin upload setting
    |--------------------------------------------------------------------------
    |
    | File system configuration for form upload files and images, including
    | disk and upload path.
    |
    */
    'upload' => [

        // Disk in `config/filesystem.php`.
        'disk' => 'public',

        // Image and file upload path under the disk above.
        'directory' => [
            'image' => 'images',
            'file'  => 'files',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin database settings
    |--------------------------------------------------------------------------
    |
    | Here are database settings for dcat-admin builtin model & tables.
    |
    */
    'database' => [

        // Database connection for following tables.
        'connection' => '',

        // User tables and model.
        'users_table' => 'block_control_admin_users',
        'users_model' => \App\Models\BlockControlAdminUsers::class,

        // Role table and model.
        'roles_table' => 'block_control_admin_roles',
        'roles_model' => \App\Models\BlockControlAdminRoles::class,

        // Permission table and model.
        'permissions_table' => 'block_control_admin_permissions',
        'permissions_model' => \App\Models\BlockControlAdminPermissions::class,

        // Menu table and model.
        'menu_table' => 'block_control_admin_menu',
        'menu_model' => \App\Models\BlockControlAdminMenu::class,

        // Pivot table for table above.
        'operation_log_table'    => 'block_control_admin_operation_log',
        'role_users_table'       => 'block_control_admin_role_users',
        'role_permissions_table' => 'block_control_admin_role_permissions',
        'role_menu_table'        => 'block_control_admin_role_menu',
        'permission_menu_table'  => 'block_control_admin_permission_menu',
    ],

    /*
    |--------------------------------------------------------------------------
    | User operation log setting
    |--------------------------------------------------------------------------
    |
    | By setting this option to open or close operation log in dcat-admin.
    |
    */
    'operation_log' => [

        'enable' => true,

        // Only logging allowed methods in the list
        'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'],

        'secret_fields' => [
            'password',
            'password_confirmation',
        ],

        // Routes that will not log to database.
        // All method to path like: auth/logs/*/edit
        // or specific method to path like: get:auth/logs.
        'except' => [
            'auth/logs*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin map field provider
    |--------------------------------------------------------------------------
    |
    | Supported: "tencent", "google", "yandex".
    |
    */
    'map_provider' => 'google',

    /*
    |--------------------------------------------------------------------------
    | Application layout
    |--------------------------------------------------------------------------
    |
    | This value is the layout of admin pages.
    */
    'layout' => [
        // indigo, blue, blue-light, blue-dark, green
        'color' => 'indigo',

        'body_class' => '',

        'sidebar_collapsed' => false,

        'sidebar_dark' => false,

        'dark_mode_switch' => false,

        // bg-primary, bg-info, bg-warning, bg-success, bg-danger, bg-dark
        'navbar_color' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | The exception handler class
    |--------------------------------------------------------------------------
    |
    */
    'exception_handler' => \Dcat\Admin\Exception\Handler::class,

    /*
    |--------------------------------------------------------------------------
    | Enable default breadcrumb
    |--------------------------------------------------------------------------
    |
    | Whether enable default breadcrumb for every page content.
    */
    'enable_default_breadcrumb' => true,

    /*
    |--------------------------------------------------------------------------
    | Settings for extensions.
    |--------------------------------------------------------------------------
    |
    | You can find all available extensions here
    | https://github.com/dcat-admin-extensions.
    |
    */
    'extensions' => [

    ],
];
