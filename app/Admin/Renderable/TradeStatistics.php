<?php


namespace App\Admin\Renderable;


use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Support\LazyRenderable;
use App\Admin\Metrics\Agent as AgentCard;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Card;

class TradeStatistics extends LazyRenderable
{

    public function render()
    {
        $id = $this->key;

        $row = new Row();
        $row->column(4, function (Column $column) {
            $column->row(new AgentCard\TotalUsers());
        });
        $row->column(4, function (Column $column) {
            $column->row(new AgentCard\Option());
        });
        $row->column(4, function (Column $column) {
            $column->row(new AgentCard\Exchange());
        });

        return Card::make('',$row);
    }

}
