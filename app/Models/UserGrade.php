<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class UserGrade extends Model
{
    //用户等级

    protected $table = 'user_grade';
    protected $primaryKey = 'grade_id';
    protected $guarded = [];

    protected $appends = ['upgrade_condition_text','bonus_text','bonus_arr'];

    public static function get_grade_info($grade_id)
    {
        return self::query()->find($grade_id);
    }

    public function getBonusTextAttribute()
    {
        $app_locale = App::getLocale();
        if($app_locale == 'zh-CN' || $app_locale == 'zh-TW'){
            if(blank($this->bonus)){
                $str = '无分红';
            }else{
                $arr = explode('|',$this->bonus);
                $size = count($arr);
                $str = '下级' . $size . '层账户（包含第'.$size.'层）下单金额的'.$arr[0].'%';
            }
        }else{
            if(blank($this->bonus)){
                $str = 'No dividends';
            }else{
                $arr = explode('|',$this->bonus);
                $size = count($arr);
                $str = 'subordinate ' . $size . ' hierarchy account '. $arr[0].'%' .' of the order amount';
            }
        }

        return $str;
    }

    public function getGradeNameAttribute($v)
    {
        $app_locale = App::getLocale();

        if($app_locale == 'zh-TW'){
            return $this->grade_name_tw;
        }elseif($app_locale == 'zh-CN'){
            return $v;
        }else{
            return $this->grade_name_en;
        }
    }

    public function getBonusArrAttribute()
    {
        return blank($this->bonus) ? [] : explode('|',$this->bonus);
    }

    public function getUpgradeConditionTextAttribute()
    {
        $app_locale = App::getLocale();

        $text_arr = [];
        if($app_locale == 'zh-CN' || $app_locale == 'zh-TW'){
            if($this->ug_recommend_grade && $this->ug_recommend_num){
                if($this->ug_recommend_grade == 1){
                    $grade_name = '实名账户';
                }else{
                    $grade_name = $this->newQuery()->where('grade_id',$this->ug_recommend_grade)->value('grade_name');
                }
                $text_arr[] = '直推 ≥ '.$this->ug_recommend_num.'个' . $grade_name;
            }
            if ($this->ug_direct_vol && $this->ug_direct_vol_num){
                $text_arr[] = '直推 ≥ '.$this->ug_direct_vol_num . '个账户交易量' . ' ≥ ' . $this->ug_direct_vol . 'USDT';
            }
            if ($this->ug_direct_recharge && $this->ug_direct_recharge_num){
                $text_arr[] = '≥ '.$this->ug_direct_recharge_num . '个账户累计充值' . ' ≥ ' . $this->ug_direct_recharge . 'USDT';
            }
            if ($this->ug_self_vol > 0){
                $text_arr[] = '自身账户交易量 ≥ ' . $this->ug_self_vol . 'USDT';
            }
            if ($this->ug_total_vol > 0){
                $text_arr[] = '直推总交易量 ≥ ' . $this->ug_total_vol . 'USDT';
            }
        }else{
            if($this->ug_recommend_grade && $this->ug_recommend_num){
                if($this->ug_recommend_grade == 1){
                    $grade_name = 'Real-name accounts';
                }else{
                    $grade_name = $this->newQuery()->where('grade_id',$this->ug_recommend_grade)->value('grade_name_en');
                }
                $text_arr[] = 'Directly recommended ≥ '.$this->ug_recommend_num.'个' . $grade_name;
            }
            if ($this->ug_direct_vol && $this->ug_direct_vol_num){
                $text_arr[] = 'Directly recommended ≥ '.$this->ug_direct_vol_num . ' Account volume' . ' ≥ ' . $this->ug_direct_vol . 'USDT';
            }
            if ($this->ug_direct_recharge && $this->ug_direct_recharge_num){
                $text_arr[] = '≥ '.$this->ug_direct_recharge_num . ' Accumulated account recharge' . ' ≥ ' . $this->ug_direct_recharge . 'USDT';
            }
            if ($this->ug_self_vol > 0){
                $text_arr[] = "Trading volume of one\'s own account ≥ " . $this->ug_self_vol . 'USDT';
            }
            if ($this->ug_total_vol > 0){
                $text_arr[] = 'Direct recommendation of total user transaction volume ≥ ' . $this->ug_total_vol . 'USDT';
            }
        }

        return $text_arr;
    }

}
