<?php

namespace App\Admin\Controllers;

use App\Models\ContractPair;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;
use Illuminate\Support\Facades\Cache;

class ContractRiskController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('合约风控')
            ->body(new Card(new \App\Admin\Forms\ContractRisk()));
    }
}
