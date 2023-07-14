<?php

namespace App\Admin\Forms\ContractAccount;

use App\Models\Coins;
use App\Models\SustainableAccount;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Recharge extends Form
{
    // 增加一个自定义属性保存ID
    protected $user_id;

    // 构造方法的参数必须设置默认值
    public function __construct($user_id = null)
    {
        $this->user_id = $user_id;

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
//         dd($input);
        // return $this->error('Your error message.');

        $user_id = $input['user_id'] ?? null;
        if (! $user_id) {
            return $this->error('参数错误');
        }
        $user = User::query()->find($user_id);
        if (! $user) return $this->error('记录不存在');
        $account = SustainableAccount::query()->where('user_id',$user_id)->first();
        if (! $account) return $this->error('记录不存在');
        $amount = $input['amount'];
        $note = $input['note'] ?? '';

        DB::beginTransaction();
        try{

            //增加用户资产
            $user->update_wallet_and_log($account['coin_id'],'usable_balance',$amount,UserWallet::sustainable_account,'admin_recharge',$note);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('操作成功');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('amount','金额')->help('数字前加-（减号），就是扣除');
        $this->textarea('note','备注');

        // 设置隐藏表单，传递用户id
        $this->hidden('user_id')->value($this->user_id);
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
