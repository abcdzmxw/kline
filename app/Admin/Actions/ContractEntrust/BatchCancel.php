<?php

namespace App\Admin\Actions\ContractEntrust;

use App\Exceptions\ApiException;
use App\Models\ContractEntrust;
use App\Models\ContractPosition;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchCancel extends BatchAction
{
    protected $style = 'btn btn-sm btn-default';

    /**
     * @return string
     */
	protected $title = '批量撤单';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $keys = $this->getKey();

        $entrusts = ContractEntrust::query()->find($keys);

        try{
            DB::beginTransaction();

            foreach ($entrusts as $entrust) {
                if(!$entrust->can_cancel()) continue;
                $this->cancel($entrust);
            }

            DB::commit();

            return $this->response()->success('Processed successfully.')->refresh();
        }catch (\Exception $e){
            DB::rollBack();
            return $this->response()->error('Processed fail.');
        }
    }

    private function cancel($entrust)
    {
        DB::beginTransaction();
        try{
            //更新委托
            $res = $entrust->update([
                'status' => 0,
                'cancel_time' => time(),
            ]);

            $user = User::query()->findOrFail($entrust['user_id']);
            if($entrust['order_type'] == 1){
                // 开仓方向
                // 合约保证金账户
                $wallet = SustainableAccount::query()->where(['user_id' => $entrust['user_id']])->first();
                if(blank($wallet)) throw new ApiException('账户类型错误');
                $log_type = 'cancel_open_position'; // 撤销合约委托
                $log_type2 = 'cancel_open_position_fee'; // 撤销合约委托
                //退还用户可用资产 冻结保证金
                $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',$entrust['margin'],UserWallet::sustainable_account,$log_type,'',$entrust['contract_id']);
                $user->update_wallet_and_log($wallet['coin_id'],'freeze_balance',-$entrust['margin'],UserWallet::sustainable_account,$log_type,'',$entrust['contract_id']);
                $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',$entrust['fee'],UserWallet::sustainable_account,$log_type2,'',$entrust['contract_id']);
                $user->update_wallet_and_log($wallet['coin_id'],'freeze_balance',-$entrust['fee'],UserWallet::sustainable_account,$log_type2,'',$entrust['contract_id']);
            }else{
                // 平仓方向
                $position_side = $entrust['side'] == 1 ? 2 : 1;
                // 持仓信息
                $position = ContractPosition::getPosition(['user_id'=>$user['user_id'],'contract_id'=>$entrust['contract_id'],'side'=>$position_side]);
                if(blank($position)) throw new ApiException();
                // 回退持仓数量
                $position->update([
                    'avail_position' => $position->avail_position + $entrust['amount'],
                    'freeze_position' => $position->freeze_position - $entrust['amount'],
                ]);
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new ApiException($e->getMessage());
        }
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确定' . $this->title . '?'];
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }

    public function actionScript(){
        $warning = "请选择撤销的委托！";

        return <<<JS
function (data, target, action) {
    console.log('发起请求之前', {data, target, action});
    var key = {$this->getSelectedKeysScript()}

    if (key.length === 0) {
        Dcat.warning('{$warning}');
        return false;
    }

    // 设置主键为复选框选中的行ID数组
    action.options.key = key;
}
JS;
    }
    protected function html()
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini">{$this->title()}</button></a>
HTML;
    }

}
