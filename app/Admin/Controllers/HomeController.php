<?php

namespace App\Admin\Controllers;

use App\Admin\Metrics\Examples;
use App\Admin\Metrics\Dashboard as Home;
use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\Dashboard;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;

class HomeController extends Controller
{
    public static $css = [
        '/static/css/home.css',
    ];

    public function index(Content $content)
    {
        Admin::css(static::$css);

        return $content
            ->header('Dashboard')
            ->description('Description...')
            ->body(function (Row $row) {
                $row->column(6, function (Column $column) {
                    $column->row(new Home\TotalUsers());
                    $column->row(new Home\Tickets());
                });

                $row->column(6, function (Column $column) {
                    $column->row(new Home\Option());
                    $column->row(new Home\Exchange());
                    $column->row(new Home\ProductOrders());
                });
            });
    }
}
