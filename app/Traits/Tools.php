<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use  Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Medz\IdentityCard\China\Identity;

trait Tools
{


    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys   要排序的键字段
     * @param string $sort  排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    function arraySort($array, $keys, $sort = SORT_DESC) {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /*自定义验证错误*/
    public function verifyField($input,$rules){
        $messages = [
            'required'   => '缺少必要字段:attribute.',
            'min'        => ':attribute字段的值不能低于:min',
            'image'      => ':attribute字段的值必须是图片',
            'alpha_dash' => ':attribute验证字段值是否仅包含字母、数字、破折号（ - ）以及下划线（ _ ）',
            'integer'    => ':attribute字段值必须是整数',
            'string'     => ':attribute字段值必须是字符串',
            'present'    => ':attribute字段必须出现，并且数据可以为空',
            'email'      => ':attribute字段格式不正确',
            'max'		=>':attribute字段的值不能高于:max',
            'size'		=>':attribute字段最少需要:size位长度',
            'digits'		=>':attribute格式有误,必须为:digits位数字',
            'alpha'		=>':attribute必须是字符',
            'in'		=>':attribute不在范围内',
            'mimes'		=>'上传文件类型不符合要求',
            'regex' => ':attribute 不合规范'
        ];

        // $errors = Validator::make($input,$rules, $messages)->errors();
        $errors = Validator::make($input,$rules)->errors();
//dd($errors->first());
        if(!blank($errors->first())) return response()->json(['code'=>1004,'message'=>$errors->first()]);
    }

    /*生成邀请码*/
    public function createInviteCode($len = 8)
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d').substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for(
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < $len;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;

    }

    /**
     * 验证是否为合法的身份证号码
     */
    function isIdentityCard($number){
        $identity = new Identity($number);
        return $identity->legal();
    }

    /*银行卡正则*/
    function isBankCard($cardNo)
    {
        if (preg_match("/^[1-9][0-9]{14,19}$/",$cardNo)){
            return true;
        }
        return false;
    }

    //比特币地址正则
    public function isBTCAddress($value){
        // BTC地址合法校验33/34
        if (!(preg_match('/^(1|3|2)[a-zA-Z\d]{24,36}$/', $value) && preg_match('/^[^0OlI]{25,36}$/', $value))) {
            return false;//满足if代表地址不合法
        }
        return true;
    }

    //以太坊地址正则
    public function isETHAddress($value)
    {
        if (!is_string($value)) {
            return false;
        }
        return (preg_match('/^0x[a-fA-F0-9]{40}$/', $value) >= 1);
    }

    //中国手机号正则
    public function isCNPhone($phone)
    {
        return preg_match("/^1[23456789]\d{9}$/", $phone);
    }

    //邮箱正则
    public function isEmail($email)
    {
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        return preg_match($pattern, $email);
    }

    //日期正则
    public function isDateString($dateTime)
    {
//        $dateTime="2010-6-4 00:00:00";

        if(preg_match("/^d{4}-d{2}-d{2} d{2}:d{2}:d{2}$/s",$dateTime))
        {
            return true;
        }else{
            return false;
        }
    }

}
