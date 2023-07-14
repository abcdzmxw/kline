<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Recharge\Pass;
use App\Admin\Forms\Recharge\Check;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\Recharge;
use App\Models\UserWallet;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Filter;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Support\Facades\Cache;

class RechargeController extends AdminController
{

    public function statistics()
    {
        $grades = AgentGrade::getCachedGradeOption();
        $builder = Recharge::query();
        $params = request()->only(array_merge($grades,['status','type','account_type','coin_name','user_id','username','datetime']));

        if(!empty($params)){
//            if(!empty($params['status'])){
//                $builder->where('status',$params['status']);
//            }
            if(!empty($params['type'])){
                $builder->where('type',$params['type']);
            }
            if(!empty($params['account_type'])){
                $builder->where('account_type',$params['account_type']);
            }
            if(!empty($params['user_id'])){
                $builder->where('user_id',$params['user_id']);
            }
            if(!empty($params['coin_name'])){
                $builder->where('coin_name',$params['coin_name']);
            }
            if(!empty($params['username'])){
                $username = $params['username'];
                $builder->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
            }
            if(!empty($params['datetime']) && !empty($params['datetime']['start']) && !empty($params['datetime']['end'])){
                $start = !empty($params['datetime']['start']) ? strtotime($params['datetime']['start']) : null;
                $end = !empty($params['datetime']['end']) ? strtotime($params['datetime']['end']) : null;
                $builder->whereBetween('datetime',[$start,$end+86399]);
            }

            $lk = last(array_keys($grades));
            foreach ($grades as $k=>$v){
                $key = 'A' . ($k+1);
                if ( $k == $lk && !empty($params[$key]) ){
                    $id = $params[$key];
                    $builder->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                }elseif( !empty($params[$key]) ){
                    $ids = Agent::getBaseAgentIds($params[$key]);
                    $builder->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                }
            }
        }
        $total = $builder->count();
        $records = $builder->groupBy('coin_name')->selectRaw('sum(amount) as amount_sum, coin_name')->pluck('amount_sum','coin_name');
        $usdt_amount = $records['USDT'] ?? 0;
        $eth_amount = $records['ETH'] ?? 0;
        $btc_amount = $records['BTC'] ?? 0;
//        $usdt_amount = 0;
//        foreach ($records as $coin_name => $amount){
//            $symbol = strtolower($coin_name) . 'usdt';
//            if($coin_name == 'USDT'){
//                $price = 1;
//            }else{
//                $price = Cache::store('redis')->get('market:' . $symbol . '_detail')['close'] ?? 1;
//            }
//            $usdt_amount = PriceCalculate($usdt_amount,'+',PriceCalculate($amount ,'*' ,$price,4),4);
//        }
//        $total_amount = $builder->sum('amount');

        $con = '<code>总单数：'.$total.'</code> '.'<code>USDT金额：'.$usdt_amount.'</code> '.'<code>ETH金额：'.$eth_amount.'</code> '.'<code>BTC金额：'.$btc_amount.'</code>';
        // $con = '<code>总单数：'.$total.'</code> '.'<code>USDT金额：'.$usdt_amount.'</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Recharge::with(['user','user_auth']), function (Grid $grid) {
            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->model()->orderByRaw("FIELD(status," . implode(",", array_keys(Recharge::$statusMap)) . ")")->orderByDesc('id');

            // xlsx
            $titles = ['id' => 'ID', 'user_id'=>'UID','username'=>'用户名','coin_name' => '币名', 'amount' => '金额','address'=>'充币地址','datetime'=>'时间','status'=>'状态'];
            $grid->export()->titles($titles)->rows(function (array $rows) use ($titles){
                foreach ($rows as $index => &$row) {
                    $row['datetime'] = date('Y-m-d H:i:s', $row['datetime']);
                    $row['status'] = Recharge::$statusMap[$row['status']];
                }
                return $rows;
            })->xlsx();

            $grid->setActionClass(Grid\Displayers\Actions::class);

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if($actions->row->status == Recharge::status_wait){
                    $actions->append(new Pass());
                }
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableEditButton();
            $grid->disableBatchDelete();
//            $grid->disableRowSelector();

            $grid->id->sortable();
            $grid->user_id;
            $grid->username;
            $grid->column('user_auth.realname','姓名');

//            $grid->coin_id;
            $grid->coin_name;
            $grid->amount->display(function($v){
                return custom_number_format($v,8);
            });
            $grid->column('account_type','账户类型')->using(UserWallet::$accountOptions)->label();
//            $grid->collection_wallet;
            $grid->address->limit(20)->responsive();
            $grid->type->using(Recharge::$typeMap);
            $grid->note;
            $grid->datetime->display(function ($datetime) {
                return date('Y-m-d H:i:s', $datetime);
            });
//            $grid->status->using(Recharge::$statusMap)->dot([0=>'danger',1=>'success',2=>'primary'])->filter(
//                Grid\Column\Filter\In::make(Recharge::$statusMap)
//            );

            $grid->filter(function (Grid\Filter $filter){
                $grades = AgentGrade::getCachedGradeOption();
                $lk = last(array_keys($grades));
                foreach ($grades as $k=>$v){
                    $key = 'A' . ($k+1);
                    $next_key = 'A' . ($k+2);
                    if($k == 0){
                        $options1 = Agent::query()->where(['deep'=>0,'is_agency'=>1])->pluck('username','id');
                        $filter->where($key,function ($q){
                            $ids = Agent::getBaseAgentIds($this->input);
                            $q->whereHas('user',function($q)use($ids){
                                $q->whereIn('referrer',$ids);
                            });
                        },$v)->select($options1)->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                    }elseif($k == $lk){
                        $filter->where($key,function ($q){
                            $id = $this->input;
                            $q->whereHas('user',function($q)use($id){
                                $q->where('referrer',$id);
                            });
                        },$v)->select()->placeholder('请选择')->width(2);
                    }else{
                        $filter->where($key,function ($q){
                            $ids = Agent::getBaseAgentIds($this->input);
                            $q->whereHas('user',function($q)use($ids){
                                $q->whereIn('referrer',$ids);
                            });
                        },$v)->select()->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                    }
                }
    
                $filter->equal('coin_name')->width(2);
                $filter->whereBetween('datetime',function ($q){
                    $start = !empty($this->input['start']) ? strtotime($this->input['start']) : null;
                    $end = !empty($this->input['end']) ? strtotime($this->input['end']) : null;
//                    dd($this->input['end'],$end);
                    $q->whereBetween('datetime',[$start,$end+86399]);
                })->date()->width(4);
                $filter->equal('user_id','UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(2);

//                $filter->equal('status','状态')->select(Recharge::$statusMap)->width(2);
                $filter->equal('account_type','账户类型')->select(UserWallet::$accountOptions)->width(2);
                $filter->equal('type','充值类型')->select(Recharge::$typeMap)->width(2);


            });

        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Recharge(), function (Show $show) {
            $show->id;
            $show->user_id;
            $show->username;
//            $show->coin_id;
            $show->coin_name;
            $show->collection_wallet;
            $show->datetime;
            $show->amount;
            $show->status;
            $show->address;
            $show->created_at;
            $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Recharge(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('username');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('collection_wallet');
            $form->text('datetime');
            $form->text('amount');
            $form->text('status');
            $form->text('address');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
