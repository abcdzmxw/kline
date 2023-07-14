<?php

namespace App\Admin\Forms\Recharge;

use App\Models\Recharge;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Check extends Form
{
    // 增加一个自定义属性保存ID
    protected $id;

    // 构造方法的参数必须设置默认值
    public function __construct($id = null)
    {
        $this->id = $id;

        parent::__construct();
    }

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
        // return $this->error('Your error message.');

//        return $this->success('Processed successfully.', '/');
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? 0;
        $remark = $input['remark'] ?? '';

        if (! $id) {
            return $this->error('参数错误');
        }

        $recharge = Recharge::query()->find($id);
        if (! $recharge) {
            return $this->error('记录不存在');
        }

        DB::beginTransaction();
        try{
            // 更新记录
            $recharge->update(['status' => $status , 'check_time' => time() ,'remark' => $remark]);

            // TODO 处理后续业务
            if($status == 1){
                // 审核通过
                $user = User::query()->find($recharge['user_id']);
                $wallet = UserWallet::query()->where(['user_id'=>$user['user_id'],'coin_id'=>$recharge['coin_id']])->first();
                //增加用户资产
                $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',$recharge['amount'],UserWallet::asset_account,'recharge');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('审核成功');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->radio('status')->options(array_only(Recharge::$statusMap,[1,2]))->rules('required|in:1,2');
        $this->textarea('remark','备注');

        // 设置隐藏表单，传递用户id
        $this->hidden('id')->value($this->id);
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }
}
