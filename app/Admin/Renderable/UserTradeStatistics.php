<?php


namespace App\Admin\Renderable;


use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Support\LazyRenderable;
use App\Admin\Metrics\User as UserCard;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Card;

class UserTradeStatistics extends LazyRenderable
{

    public function render()
    {
        $id = $this->key;

        $row = new Row();
        $row->column(4, function (Column $column) {
            $column->row(new UserCard\TotalUsers());
        });
        $row->column(4, function (Column $column) {
            $column->row(new UserCard\Option());
        });
        $row->column(4, function (Column $column) {
            $column->row(new UserCard\Exchange());
        });

        return Card::make('',$row);
    }

}
