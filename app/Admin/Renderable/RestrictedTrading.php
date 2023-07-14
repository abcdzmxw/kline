<?php


namespace App\Admin\Renderable;


use App\Models\UserWallet;
use App\Models\UserRestrictedTrading;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Support\LazyRenderable;
use App\Admin\Metrics\User as UserCard;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Table;

class RestrictedTrading extends LazyRenderable
{

    public function render()
    {
        $id = $this->key;

        $data = UserRestrictedTrading::query()->where('user_id',$id)->get(['user_id', 'coin_name', 'type', 'direction','status'])->toArray();

        $titles = [
            'UID',
            '币种',
            '限制类型',
            '方向',
            '状态',
        ];
        foreach($data as $k=>$v){
            if($v['type'] == 1){
                $data[$k]['type'] = '币币交易';
            }else{
                $data[$k]['type'] = '闪兑交易';
            }
            if($v['direction'] == 1){
                $data[$k]['direction'] = '买入';
            }else{
                $data[$k]['direction'] = '卖出';
            }
            if($v['status'] == 1){
                $data[$k]['status'] = '限制';
            }else{
                $data[$k]['status'] = '不限制';
            }
        }

        return Table::make($titles, $data);
    }

}
