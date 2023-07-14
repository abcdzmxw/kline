<?php

namespace App\Admin\Repositories;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\BonusLog as BonusLogModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Repositories\EloquentRepository;

class BonusLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = BonusLogModel::class;

    public function get(Grid\Model $model)
    {
//        // 当前页数
//        $currentPage = $model->getCurrentPage();
//        // 每页显示行数
//        $perPage = $model->getPerPage();
//
//        $start = ($currentPage - 1) * $perPage;
//
//        // 获取排序字段
//        [$orderColumn, $orderType] = $model->getSort();

        $builder = BonusLogModel::query()->with(['user'])->groupBy('user_id')->selectRaw("sum(amount) as amount_sum, user_id");

        $query = $model->filter()->inputs();
//        dd($query);

        if (!empty($query['user_id'])) {
            // 执行你的筛选逻辑
            $builder->where('user_id',$query['user_id']);
        }
        if (!empty($query['username'])) {
            $username = $query['username'];
            // 执行你的筛选逻辑
            $builder->whereHas('user',function($q)use($username){
                $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
            });
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

        //dd($builder->get()->toArray());
        return $builder->paginate();
    }

}
