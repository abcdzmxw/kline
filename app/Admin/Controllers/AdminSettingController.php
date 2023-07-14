<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AdminSetting;
use App\Models\Admin\AdminSetting as Setting;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;

class AdminSettingController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('网站设置')
            ->body(new Card(new \App\Admin\Forms\Setting()));
    }
}
