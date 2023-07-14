<?php 
return [
    'ueditor' => [
        'imported' => true,
        'imported_at' => '2020-08-10 15:00:05',
        'enable' => true,
    ],
    'iframe-tabs' => [
        'enable' => false,
        'imported' => true,
        'imported_at' => '2020-10-19 16:37:52',
        'home_action' => 'App\Admin\Controllers\HomeController@index',
        'home_title' => 'Home',
        'home_icon' => 'fa-home',
        'use_icon' => true,
        'tabs_css' => 'vendor/laravel-admin-ext/iframe-tabs/dashboard.css',
        'layer_path' => 'vendor/laravel-admin-ext/iframe-tabs/layer/layer.js',
        'pass_urls' => [
            0 => '/auth/logout',
            1 => '/auth/lock',
        ],
        'force_login_in_top' => true,
        'tabs_left' => 42,
        'bind_urls' => 'popup',
        'bind_selecter' => 'a.grid-row-view,a.grid-row-edit,.column-__actions__ ul.dropdown-menu a,.box-header .pull-right .btn-success,.popup',
    ],
];
