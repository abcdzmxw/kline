<?php


namespace App\Admin\Controllers;

use App\Admin\Actions\Agent\AgentPertain;
use App\Admin\Actions\Agent\ChangeStatus;
use App\Admin\Metrics\Agent as AgentCard;
use App\Admin\Renderable\TradeStatistics;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\User;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Layout\Content;

class AgentController extends \Dcat\Admin\Controllers\AdminController
{
    protected $title="代理";
    public function grid(){

    return Grid::make(Agent::query()->where("is_agency",1), function (Grid $grid) {

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            $actions->disableQuickEdit();
//            $actions->disableEdit();
            $actions->disableView();

            if($actions->row->deep != 0){
                $actions->append(new AgentPertain());
            }

            if($actions->row->status == 0){
                $actions->append(new ChangeStatus());
            }
        });

//        $grid->disableViewButton();
        $grid->disableDeleteButton();
//        $grid->disableEditButton();
//        $grid->disableCreateButton();

        $grid->column('user_id','ID');

        $grid->username;
        $grid->name;
        $grid->column("deep","代理等级")->using(AgentGrade::getCachedGradeOption());
        $grid->column("up","上级代理")->display(function(){#上级代理
            $user = User::find($this->pid);
            if( empty($user) ) return "<laber style='color: ;'>无上级代理</laber>";
            return "<laber style='color: #0d77e4'>$user->username</laber>";
        });

        $grid->column('invite_code','邀请码');

//        $grid->column('user_auth_level',"是否认证")->using(Agent::$userAuthMap)->dot([0=>'danger',1=>'success']);
        $grid->column("总注册人数")->display(function (){
            if($this->deep == 4){
                $count = User::query()->where('referrer',$this->user_id)->count();
            }else{
                $baseAgentIds = Agent::getBaseAgentIds($this->user_id);
                $count = User::query()->whereIn('referrer',$baseAgentIds)->count();
            }
            return $count;
        });

//        $grid->column('content','统计')->display('统计')->expand(TradeStatistics::make());

        $grid->column('subscribe_rebate_rate','申购返佣比率');
        $grid->column('contract_rebate_rate','合约返佣比率');
        $grid->column('option_rebate_rate','期权返佣比率');

        $grid->column('status','状态')->using(Agent::$userStatusMap)->dot([0=>'danger',1=>'success']);

        $grid->created_at;

        $grid->filter(function (Grid\Filter $filter) {
            $grades = AgentGrade::getCachedGradeOption();
            $lk = last(array_keys($grades));
            foreach ($grades as $k=>$v){
                $key = 'A' . ($k+1);
                $next_key = 'A' . ($k+2);
                if($k == 0){
                    $options1 = Agent::query()->where(['deep'=>0,'is_agency'=>1])->pluck('username','id');
                    $filter->where($key,function ($q){
                        $ids = Agent::getBaseAgentIds($this->input);
                        $q->whereIn('referrer',$ids);
                    },$v)->select($options1)->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                }elseif($k == $lk){
                    $filter->where($key,function ($q){
                        $id = $this->input;
                        $q->where('referrer',$id);
                    },$v)->select()->placeholder('请选择')->width(2);
                }else{
                    $filter->where($key,function ($q){
                        $ids = Agent::getBaseAgentIds($this->input);
                        $q->whereIn('referrer',$ids);
                    },$v)->select()->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                }
            }

            $filter->equal('user_id',"代理商id")->width(2);
            $filter->equal('name',"代理商名称")->width(2);
            $filter->equal("deep","代理商等级")->select(AgentGrade::getCachedGradeOption())->width(2);
          /*  $filter->equal("deep")->select(Agent::$grade)->width(2);*/
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
        return Show::make($id, new Agent(), function (Show $show) {
            $show->user_id;
            $show->account;
            $show->account_type;
            $show->username;
            $show->pid;

            $show->path;
            $show->country_code;
            $show->phone;

            $show->password;
            $show->payword;
            $show->invite_code;
            $show->user_grade;
            $show->user_identity;
            $show->user_auth_level;
            $show->login_code;
            $show->status;
            $show->reg_ip;
            $show->last_login_time;
            $show->last_login_ip;
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
        return Form::make(new Agent(), function (Form $form) {
            $form->hidden('user_id')->readOnly();
            $form->text('username','登录账户')->rules('required:users,username');
            $form->text('name','昵称')->rules('required:users,name');

//            $form->text('invite_code','邀请码')->rules('required');

            $form->text('subscribe_rebate_rate','申购返佣比率')->required()->default(0.85);
            $form->text('contract_rebate_rate','合约返佣比率')->required()->default(0.85);
            $form->text('option_rebate_rate','期权返佣比率')->required()->default(0.85);
            $form->switch('auth_level','代理认证提币权限开关');
            $form->image("avatar","头像")->rules('required:users,avatar')->autoUpload();
            $form->password('password',"密码")->rules('required:users,password');
            $form->text("invite_code")->display(false);
            $form->text("deep")->display(false);

            $form->saving(function (Form $form){
                $password = $form->password;

                if(Hash::needsRehash($password)){
                    $pass = Hash::make($password);
                    $form->password = $pass;
                }else{
                    $form->deleteInput('password');
                }

                $form->is_agency = 1;# 1:标记为代理商

                $form->pid  = 0;#顶级代理 pid为0

                if( $form->isCreating() ) {
                    $form->invite_code = User::gen_invite_code();
                    $resu = User::query()->where(["username"=>$form->username])->first();
                    if( $resu) return $form->error("用户名已存在");

//                    if( blank($form->avatar)) return $form->error("图片不能为空");
                    $form->created_at = date("Y-m-d H:i:s");
                    $form->deep = 0;
                }

                $isInviteExist = User::query()->where(['invite_code' => $form->invite_code])->first();
                if (isset($isInviteExist)) {

                    return $form->error('邀请码已存在');
                }

            });

            $form->saved(function (Form $form) {

                $create = $form->isCreating();
                if( $create ){
                    $id = $form->getKey();

                    DB::table("users")
                        ->where("user_id",$id)
                        ->update( [
                            "id"=>$id,
                            "pid"=>0,
                            "is_agency"=>1
                        ]);
                    DB::table("agent_admin_role_users")->insert(["role_id"=>2,"user_id"=>$id]);
                }

            });


        });
    }
}
