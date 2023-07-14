<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Withdraw\Check;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\Withdraw;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Support\Facades\Cache;

class WithdrawController extends AdminController
{

    public function statistics()
    {
        $grades = AgentGrade::getCachedGradeOption();
        $builder = Withdraw::query();
        $params = request()->only(array_merge($grades,['status','coin_name','user_id','username','datetime']));

        if(!empty($params)){
            if(!empty($params['status'])){
                $builder->where('status',$params['status']);
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
            if(!empty($params['datetime']) && !empty($params['datetime']['start'])){
                $start = $params['datetime']['start'] ? strtotime($params['datetime']['start']) : null;
                $end = $params['datetime']['end'] ? strtotime($params['datetime']['end']) : null;
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
        $records = $builder->groupBy('coin_name')->selectRaw('sum(total_amount) as amount_sum, coin_name')->pluck('amount_sum','coin_name');
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
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Withdraw::with(['user','user_auth']), function (Grid $grid) {
            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->model()->orderByRaw("FIELD(status," . implode(",", array_keys(Withdraw::$statusMap)) . ")")->orderByDesc('id');
            $grid->setActionClass(Grid\Displayers\Actions::class);

            // xlsx
            $titles = ['id' => 'ID', 'user_id'=>'UID','username'=>'用户名','coin_name' => '币名', 'amount' => '金额','address'=>'充币地址','currency'=>'法币','exchange_rate'=>'汇率','net_receipts'=>'应付金额','datetime'=>'时间','status'=>'状态'];
            $grid->export()->titles($titles)->rows(function (array $rows) use ($titles){
                foreach ($rows as $index => &$row) {
                    $row['datetime'] = date('Y-m-d H:i:s', $row['datetime']);
                    $row['status'] = Withdraw::$statusMap[$row['status']];
                }
                return $rows;
            })->xlsx();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if($actions->row->status == Withdraw::status_wait){
                    $actions->append(new Check());
                }
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableEditButton();

            $grid->id->sortable();
            $grid->user_id;
            $grid->username;
            $grid->column('user_auth.realname','姓名');
            // $grid->column('user.referrer',$grades[$lk])->display(function($v){
            //     return Agent::query()->where('id',$v)->value('name');
            // });

            $grid->coin_name;
            $grid->address;
            $grid->column('total_amount','提币数量');
            $grid->column('amount','实际到账数量');
            $grid->column("withdrawal_fee","手续费");
            $grid->column("currency","法币");
            $grid->column("exchange_rate","汇率");
            $grid->column("net_receipts","应付法币");
//            $grid->coin_id;
            $grid->datetime->display(function ($datetime) {
                return date('Y-m-d H:i:s', $datetime);
            });

            $grid->status->using(Withdraw::$statusMap)->dot([0=>'danger',1=>'success',2=>'primary',3=>'info'])->filter(
                Grid\Column\Filter\In::make(Withdraw::$statusMap)
            );

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
                
                $filter->whereBetween('datetime',function ($q){
                    $start = strtotime($this->input['start']);
                    $end = strtotime($this->input['end']);
                    $q->whereBetween('datetime',[$start,$end+86399]);
                })->date()->width(4);

                $filter->equal('user_id','UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(2);
                $filter->equal('status','状态')->select(Withdraw::$statusMap)->width(2);
                $filter->equal('coin_name')->width(2);

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
        return Show::make($id, new Withdraw(), function (Show $show) {
            $show->id;
            $show->user_id;
            $show->username;
            $show->amount;
            $show->status;
            $show->coin_id;
            $show->coin_name;
            $show->address;
            $show->datetime;
            $show->agent_level;
            $show->agent_name;
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
        return Form::make(new Withdraw(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('username');
            $form->text('amount');
            $form->text('status');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('address');
            $form->text('datetime');
            $form->text('agent_level');
            $form->text('agent_name');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
