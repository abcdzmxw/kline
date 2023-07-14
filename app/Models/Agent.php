<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Agent extends Model
{
    protected $table= "users";
    protected $primaryKey = 'user_id';
    protected $guarded = [];

    //用户认证
    const user_auth_level_wait = 0;
    const user_auth_level_primary = 1;
    const user_auth_level_top = 2;
    public static $userAuthMap = [
        self::user_auth_level_wait => '未认证',
        self::user_auth_level_primary => '初级认证',
        self::user_auth_level_top => '高级认证',
    ];
   /* public function getStatusTextAttribute()
    {
        return self::$userStatusMap[$this->status];
    }*/

    //用户状态
    const user_status_freeze = 0;//冻结
    const user_status_normal = 1;//正常
    public static $userStatusMap = [
        self::user_status_freeze => '未激活',
        self::user_status_normal => '正常',
    ];


    const agent_code0 = 0;
    const agent_code1 = 1;
    const agent_code2 = 2;
    const agent_code3 = 3;
    const agent_code4 = 4;

    public static $grade = [
//        self::agent_code0 => '超级管理员',
        self::agent_code0 => 'A1',
        self::agent_code1 => 'A2',
        self::agent_code2 => "A3",
        self::agent_code3 => "A4",
        self::agent_code4 => "A5",
    ];

    static function getUser($id){
        $agent = Agent::all()->toArray();
        $res = self::getChildren($agent,$id);

        return $res;
    }

    static function getChildren($data,$pid,$tmp=[]){
        foreach ($data as $v) {
            if ($v['pid']==$pid) {
                $tmp[]=$v['user_id'];
                $tmp = self::getChildren($data,$v['user_id'],$tmp);
            }
        }
        return $tmp;
    }

    //获取传入的分类的无限子类ids
    public static function getSubAgentIds($id)
    {
        $items = self::query()->where('is_agency',1)->select('id','pid')->get();

        if(blank($items)){
            return [];
        }else{
            $items = $items->toArray();
        }

        $subIds = get_tree_child($items,$id);

        return $subIds;
    }

    //获取最下级基础代理IDS
    public static function getBaseAgentIds($id,$deep = 4)
    {
        $items = self::query()->where('is_agency',1)->select('id','pid','deep')->get();
        if(blank($items)) return [];

        // $agent = $items->where('id',$id)->first();
        // return [$id];
        // if($agent['deep'] == 4) return [$id];

        $items = $items->toArray();

        $subIds = get_agent_child($items,$id);
        $subIds[] = $id;

        return $subIds;
    }
//    public static function getBaseAgentIds($id,$deep = 4)
//    {
//        $items = self::query()->where('is_agency',1)->select('id','pid','deep')->get();
//        if(blank($items)) return [];
//
//        $agent = $items->where('id',$id)->first();
//        if($agent['deep'] == 4) return [$id];
//
//        $items = $items->toArray();
//
//        $subIds = get_agent_child($items,$id,$deep);
//
//        return $subIds;
//    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Agent', 'pid','id');
    }

    public function children()
    {
        return $this->hasMany('App\Models\Agent', 'referrer','user_id');
    }

    public function direct_user_count()
    {
        return $this->children()->count();
    }

}
