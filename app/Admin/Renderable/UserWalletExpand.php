<?php


namespace App\Admin\Renderable;


use App\Models\UserWallet;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Support\LazyRenderable;
use App\Admin\Metrics\User as UserCard;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Table;

class UserWalletExpand extends LazyRenderable
{

    public function render()
    {
        $id = $this->key;

        $data = UserWallet::query()->where('user_id',$id)->get(['user_id', 'coin_name', 'usable_balance', 'freeze_balance'])->toArray();

        $titles = [
            'UID',
            '币种',
            '可用',
            '冻结',
        ];

        return Table::make($titles, $data);
    }

}
