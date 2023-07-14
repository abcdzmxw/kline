<?php


namespace App\Admin\Repositories;


use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\ContractEntrust;
use App\Models\Recharge;
use App\Models\SustainableAccount;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Dcat\Admin\Repositories\EloquentRepository;
use Dcat\Admin\Grid;

class ContractAnomaly extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = SustainableAccount::class;

    public function get(Grid\Model $model)
    {
        // 获取当前页数
        $currentPage = $model->getCurrentPage();
        // 获取每页显示行数
        $perPage = $model->getPerPage();

        $start = ($currentPage - 1) * $perPage;

        $builder = SustainableAccount::query()->with('user');

        // 获取排序字段
        [$orderColumn, $orderType] = $model->getSort();

        // 查询query
        $query = $model->filter()->inputs();
//        dump($query);
        if (!empty($query['user_id'])) {
            // 执行你的筛选逻辑
            $builder->where('user_id',$query['user_id']);
        }
        if (!empty($query['created_at.start']) && !empty($query['created_at.end'])) {
            // 执行你的筛选逻辑
            $start = $query['created_at.start'];
            $end = $query['created_at.end'];
            $builder->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
        }
        $grades = AgentGrade::getCachedGradeOption();
        $lk = last(array_keys($grades));
        foreach ($grades as $k=>$v){
            $key = 'A' . ($k+1);
            if ( $k == $lk && !empty($query[$key]) ){
                $id = $query[$key];
                $builder->whereHas('user',function($q)use($id){
                    $q->where('referrer',$id);
                });
            }elseif( !empty($query[$key]) ){
                $ids = Agent::getBaseAgentIds($query[$key]);
                $builder->whereHas('user',function($q)use($ids){
                    $q->whereIn('referrer',$ids);
                });
            }
        }

//        dd($builder->get()->toArray());
        $total = $builder->count();
//        $data = $builder->skip($start)->take($perPage)->get();
        $data = $builder->get();
        $data = blank($data) ? [] : $data->toArray();
        foreach ($data as $k => $item){
            // 合约转入
            $contract_in = UserWalletLog::query()
                    ->where('user_id',$item['user_id'])
                    ->where('rich_type','usable_balance')
                    ->where('account_type',UserWallet::sustainable_account)
                    ->where('log_type','fund_transfer')
                    ->where('amount','>',0)
                    ->sum('amount');
            // 合约转出
            $contract_out = UserWalletLog::query()
                    ->where('user_id',$item['user_id'])
                    ->where('rich_type','usable_balance')
                    ->where('account_type',UserWallet::sustainable_account)
                    ->where('log_type','fund_transfer')
                    ->where('amount','<',0)
                    ->sum('amount');
            // 手续费
            $fee = UserWalletLog::query()
                ->where('user_id',$item['user_id'])
                ->where('rich_type','usable_balance')
                ->where('account_type',UserWallet::sustainable_account)
                ->whereIn('log_type',['open_position_fee','close_position_fee','system_close_position_fee','cancel_open_position_fee'])
                ->sum('amount');
            // 资金费
            $cost = UserWalletLog::query()
                ->where('user_id',$item['user_id'])
                ->where('rich_type','usable_balance')
                ->where('account_type',UserWallet::sustainable_account)
                ->where('log_type','position_capital_cost')
                ->sum('amount');
            // 盈亏
            $profit = ContractEntrust::query()
                ->where('user_id',$item['user_id'])
                ->where('status',ContractEntrust::status_completed)
                ->sum('profit');
            // 合约扣款
            $charge = Recharge::query()
                ->where('user_id',$item['user_id'])
                ->where('type',2)
                ->where('account_type',UserWallet::sustainable_account)
                ->sum('amount');

            $data[$k]['contract_in'] = $contract_in;
            $data[$k]['contract_out'] = $contract_out;
            $data[$k]['contract_charge'] = $charge;
            $data[$k]['contract_fee'] = $fee;
            $data[$k]['contract_cost'] = $cost;
            $data[$k]['contract_profit'] = $profit;
            $data[$k]['theory_balance'] = PriceCalculate(($contract_in + $contract_out - abs($fee) - abs($cost) + $profit - $item['used_balance'] - $item['freeze_balance']) ,'+', $charge,8);
            $data[$k]['anomaly_balance'] = PriceCalculate($data[$k]['theory_balance'],'-',$item['usable_balance'],8);
        }

        // 只显示异常
        if (!empty($query['anomaly'])) {
            if($query['anomaly'] == 1){
                $data = array_where($data,function ($value, $key){
                    return $value['anomaly_balance'] != 0;
                });
            }else{
                $data = array_where($data,function ($value, $key){
                    return $value['anomaly_balance'] == 0;
                });
            }
        }

//        dd($data);
        return $model->makePaginator(
            $total, // 传入总记录数
            $data  // 传入数据二维数组
        );
    }
}
