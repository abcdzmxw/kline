<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Controllers\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
    protected $view = 'admin.login.login';
}
