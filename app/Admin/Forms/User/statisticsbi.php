<?php

namespace App\Admin\Forms\User;

use App\Models\Coins;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserRestrictedTrading;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class statisticsbi extends Form
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
        $coin_id = $input['coin_id'];
        $coin = Coins::query()->find($coin_id);
        if (! $coin) return $this->error('记录不存在');
        $direction = $input['direction'];
        $status = $input['status'];
        $type = $input['type'];

        DB::beginTransaction();
        try{

            foreach($direction as $k=>$v){
                // 获取限制数据
                $stricted_detail = UserRestrictedTrading::query()->where(['user_id'=>$user['user_id'],'coin_id'=>$coin['coin_id'],'direction'=>$v,'type'=>$type])->first();
                if(empty($stricted_detail)){
                    \App\Models\UserRestrictedTrading::query()->create([
                        'user_id' => $user['user_id'],
                        'coin_id' => $coin['coin_id'],
                        'coin_name' => $coin['coin_name'],
                        'type'      => $type,
                        'direction' => $v,
                        'status' => $status,
                    ]);
                }else{
                    $stricted_detail->update([
                        'direction' => $v,
                        'status' => $status,
                    ]);
                }
            }
            
            

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('充值成功');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('coin_id','币种')->options(\App\Models\Coins::getCachedCoinOption())->rules('required');

        // $this->radio('account_type','账户类型')->options(['1'=>'买入','2'=>'卖出'])->default(1)->rules('required|in:1,2');
        $this->radio('type','限制类型')->options(['1'=>'币币交易','2'=>'闪兑交易'])->default(1)->rules('required');
        $this->checkbox('direction','账户类型')->options(['1'=>'买入','2'=>'卖出'])->default(1)->rules('required');
        $this->radio('status','交易状态')->options(['1'=>'限制','2'=>'不限制'])->default(1)->rules('required');
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
