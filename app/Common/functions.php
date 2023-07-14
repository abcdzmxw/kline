<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/8/16
 * Time: 下午5:13
 * desc: 公共方法
 */

use App\Mail\VerifyCode;
use GatewayClient\Gateway;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Overtrue\EasySms\PhoneNumber;
use GuzzleHttp\Client;

if (! function_exists('user_admin_config')) {
    function user_admin_config($key = null, $value = null)
    {
        $session = session();

        if (! $config = $session->get('admin.config')) {
            $config = config('admin');

            $config['lang'] = config('app.locale');
        }

        if (is_array($key)) {
            // 保存
            foreach ($key as $k => $v) {
                Arr::set($config, $k, $v);
            }

            $session->put('admin.config', $config);

            return;
        }

        if ($key === null) {
            return $config;
        }

        return Arr::get($config, $key, $value);
    }
}

function option_risk_key($symbol)
{
    // $symbol = "btcusdt";
    return 'fkJson:' . strtoupper(str_replace('usdt','',$symbol)) . '/USDT';
}

function contract_risk_key($symbol)
{
    // $symbol = "BTC";
    return 'fkJson:' . $symbol . '/USDT';
}

// symbol 转换
function symbolMap($symbol,$mode = true)
{
    return $symbol;

//    $map = [
//        config('coin.coin2_symbol') => 'TRB',
//        config('coin.coin3_symbol') => 'AAVE',
//    ];
//
//    if($mode){
//        return $map[$symbol] ?? $symbol;
//    }else{
//        $s = array_search($symbol,$map);
//        return $s === false ? $symbol : $s;
//    }
}

/**
 * 高精度计算
 * @param $first
 * @param $second
 * @param string $type
 * @param int $pointNum
 * @return int|string|null
 */
function bcMath($first,$second,$type = '-',$pointNum = 8)
{
    switch ($type) {
        case '-':
            return bcsub($first,$second,$pointNum);
            break;
        case '+':
            return bcadd($first,$second,$pointNum);
            break;
        case '/':
            return bcdiv($first,$second,$pointNum);
            break;
        case '*':
            return bcmul($first,$second,$pointNum);
            break;
    }
    return 0;
}

function linspace($i,$f,$n){
    if($f === $i){
        $i = $i.'1';
        $f = $f.'9';
    }
    $step = ($f-$i)/($n-1);
    return range($i,$f,$step);
}

/*区块链相关函数*/
//比特币地址正则
function isBTCAddress($value)
{
    // BTC地址合法校验33/34
    if (!(preg_match('/^(1|3|2)[a-zA-Z\d]{24,36}$/', $value) && preg_match('/^[^0OlI]{25,36}$/', $value))) {
        return false;//满足if代表地址不合法
    }
    return true;
}

//以太坊地址正则
function isETHAddress($value)
{
    if (!is_string($value)) {
        return false;
    }
    return (preg_match('/^0x[a-fA-F0-9]{40}$/', $value) >= 1);
}

function getChainParamValue($chainParams, $key)
{
    if (is_array($chainParams)) {
        foreach ($chainParams as $chainParam) {
            if ($chainParam['key'] == $key) {
                return $chainParam['value'];
            }
        }
    }

    return false;
}

function base58_encode($string)
{
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $base     = strlen($alphabet);
    if (is_string($string) === false) {
        return false;
    }
    if (strlen($string) === 0) {
        return '';
    }
    $bytes   = array_values(unpack('C*', $string));
    $decimal = $bytes[0];
    for ($i = 1, $l = count($bytes); $i < $l; $i++) {
        $decimal = bcmul($decimal, 256);
        $decimal = bcadd($decimal, $bytes[$i]);
    }
    $output = '';
    while ($decimal >= $base) {
        $div     = bcdiv($decimal, $base, 0);
        $mod     = bcmod($decimal, $base);
        $output  .= $alphabet[$mod];
        $decimal = $div;
    }
    if ($decimal > 0) {
        $output .= $alphabet[$decimal];
    }
    $output = strrev($output);
    foreach ($bytes as $byte) {
        if ($byte === 0) {
            $output = $alphabet[0] . $output;
            continue;
        }
        break;
    }
    return (string)$output;
}

function base58_decode($base58)
{
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $base     = strlen($alphabet);
    if (is_string($base58) === false) {
        return false;
    }
    if (strlen($base58) === 0) {
        return '';
    }
    $indexes = array_flip(str_split($alphabet));
    $chars   = str_split($base58);
    foreach ($chars as $char) {
        if (isset($indexes[$char]) === false) {
            return false;
        }
    }
    $decimal = $indexes[$chars[0]];
    for ($i = 1, $l = count($chars); $i < $l; $i++) {
        $decimal = bcmul($decimal, $base);
        $decimal = bcadd($decimal, $indexes[$chars[$i]]);
    }
    $output = '';
    while ($decimal > 0) {
        $byte    = bcmod($decimal, 256);
        $output  = pack('C', $byte) . $output;
        $decimal = bcdiv($decimal, 256, 0);
    }
    foreach ($chars as $char) {
        if ($indexes[$char] === 0) {
            $output = "\x00" . $output;
            continue;
        }
        break;
    }
    return $output;
}

//encode address from byte[] to base58check string
function base58check_en($address)
{
    $hash0     = hash("sha256", $address);
    $hash1     = hash("sha256", hex2bin($hash0));
    $checksum  = substr($hash1, 0, 8);
    $address   = $address . hex2bin($checksum);
    $base58add = base58_encode($address);
    return $base58add;
}

//decode address from base58check string to byte[]
function base58check_de($base58add)
{
    $address = base58_decode($base58add);
    $size    = strlen($address);
    if ($size != 25) {
        return false;
    }
    $checksum  = substr($address, 21);
    $address   = substr($address, 0, 21);
    $hash0     = hash("sha256", $address);
    $hash1     = hash("sha256", hex2bin($hash0));
    $checksum0 = substr($hash1, 0, 8);
    $checksum1 = bin2hex($checksum);
    if (strcmp($checksum0, $checksum1)) {
        return false;
    }
    return $address;
}

function hexString2Base58check($hexString)
{
    $address   = hex2bin($hexString);
    $base58add = base58check_en($address);
    return $base58add;
}

function base58check2HexString($base58add)
{
    $address   = base58check_de($base58add);
    $hexString = bin2hex($address);
    return $hexString;
}

function hexString2Base64($hexString)
{
    $address = hex2bin($hexString);
    $base64  = base64_encode($address);
    return $base64;
}

function base642HexString($base64)
{
    $address   = base64_decode($base64);
    $hexString = bin2hex($address);
    return $hexString;
}

function base58check2Base64($base58add)
{
    $address = base58check_de($base58add);
    $base64  = base64_encode($address);
    return $base64;
}

function base642Base58check($base64)
{
    $address   = base64_decode($base64);
    $base58add = base58check_en($address);
    return $base58add;
}

function hex2Dec(string $hex): string
{
    $dec = 0;
    $len = strlen($hex);
    for ($i = 1; $i <= $len; $i++) {
        $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
    }
    return $dec;
}

function dec2Hex($dec): string
{
    $last   = bcmod($dec, 16);
    $remain = bcdiv(bcsub($dec, $last), 16);
    if ($remain == 0) {
        return dechex($last);
    } else {
        return dec2Hex($remain) . dechex($last);
    }
}

function hex2Str(string $hex): string
{
    $str = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $str;
}

function str2Hex(string $str): string
{
    $hex = "";
    for ($i = 0; $i < strlen($str); $i++) {
        $hex .= str_pad(dechex(ord($str[$i])), 2, "0", STR_PAD_LEFT);
    }
    return $hex;
}

/*区块链相关函数*/

function custom_number_format($value, $decimals)
{
    return number_format($value, $decimals, '.', '');
}

/**
 * 一维数据数组生成数据树
 * @param array $list 数据列表
 * @param string $id 父ID Key
 * @param string $pid ID Key
 * @param string $son 定义子数据Key
 * @return array
 */
function arr2tree($list, $id = 'id', $pid = 'pid', $son = 'sub')
{
    list($tree, $map) = [[], []];
    foreach ($list as $item) {
        $map[$item[$id]] = $item;
    }

    foreach ($list as $item) {
        if (isset($item[$pid]) && isset($map[$item[$pid]])) {
            $map[$item[$pid]][$son][] = &$map[$item[$id]];
        } else {
            $tree[] = &$map[$item[$id]];
        }
    }
    unset($map);
    return $tree;
}

/**
 * 一维数据数组生成数据树
 * @param array $list 数据列表
 * @param string $id ID Key
 * @param string $pid 父ID Key
 * @param string $path
 * @param string $ppath
 * @return array
 */
function arr2table(array $list, $id = 'id', $pid = 'pid', $path = 'path', $ppath = '')
{
    $tree = [];
    foreach (arr2tree($list, $id, $pid) as $attr) {
        $attr[$path] = "{$ppath}-{$attr[$id]}";
        $attr['sub'] = isset($attr['sub']) ? $attr['sub'] : [];
        $attr['spt'] = substr_count($ppath, '-');
        $attr['spl'] = str_repeat("　├　", $attr['spt']);
        $sub         = $attr['sub'];
        unset($attr['sub']);
        $tree[] = $attr;
        if (!empty($sub)) {
            $tree = array_merge($tree, arr2table($sub, $id, $pid, $path, $attr[$path]));
        }
    }
    return $tree;
}

function gatewaySend($group_id,$message)
{
    info('2-gatewaySend:' . $group_id);
    Gateway::$registerAddress = '127.0.0.1:1236';
    Gateway::sendToGroup($group_id, $message);
    info('3-gatewaySend:' . $group_id);
}
#验证手机号码
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^1[3,4,5,7,8,9]{1}[\d]{9}$#', $mobile) ? true : false;
}


#验证邮箱地址
function isEmail($str) {
    if (!$str) {
        return false;
    }
    return preg_match('#[a-z0-9&\-_.]+@[\w\-_]+([\w\-.]+)?\.[\w\-]+#is', $str) ? true : false;
}

function createWalletAddress($user_id,$coin_name)
{
    if(!in_array($coin_name,['USDT','BTC','ETH'])) return false;
    $coin = \App\Models\Coins::query()->where('coin_name',$coin_name)->first();
    if(blank($coin)) return false;
    $url = 'http://ec2-35-168-20-64.compute-1.amazonaws.com:8083/proto/address';
    $post_data = [];
    $post_data['appKey'] = $coin['appKey'];
    //用户id
    $post_data['customerNo'] = $user_id;

    $post_data['reqTime'] = time();
    $post_data['symbol'] = $coin['symbol'];
    $sign = '';
    foreach ($post_data as $key=>$val){
        $sign=$sign.$key."=".$val."&";
    }
    $sign=$sign."appSecret=".$coin['appSecret'];
    $post_data['sign']=md5($sign);
    $data = coinCurlPost($url,$post_data);
//    dd($data);
    $data = json_decode($data,true);
    if($data['code']!=0){
        return false;
    }
    $address = $data['data']['address'];
    return $address;
}


function coinCurlPost($url, $postFields)
{
    $postFields = http_build_query($postFields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT,60);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


//function chargeEth($address,$amount,$txid,$reqTime,$customerNo)
//{
//    $address_deposit = \App\Models\UserWallet::query()->where(['wallet_address'=>$address])->first();
//    $coin = \App\Models\Coins::query()->where(['coin_id'=>$address_deposit['coin_id']])->first();
//    $number =$amount;
//    $txid   =$txid;
//    $reqTime=$reqTime;
//    $sign = "address=".$address."&amount=".$number."&appKey=".$coin["appKey"]."&customerNo=".$customerNo."&reqTime="
//        .$reqTime."&symbol=".$coin["coin_name"]."&txid=".$txid."&appSecret=".$coin['appSecret'];
//    if(!$address_deposit){
//        return false;
//    }else
//     {
//         return true;
//     }
//
//
//
//}
/**
 * RSA私钥解密，需在php.ini开启php_openssl.dll扩展
 * @param string $after_encode_data 前端传来，经 RSA 加密后的数据
 * @return string 返回解密后的数据
 */
function rsa_decode($after_encode_data)
{
    $private_key = config('rsakey.private_key');
    openssl_private_decrypt(base64_decode($after_encode_data),$decode_result,$private_key);
    return $decode_result;
}

/**
 * @param $account
 * @param string $slider_type register|login
 * @return string
 */
function getSliderToken($account,$slider_type = 'register')
{
    $vKey = 'user:sliderVerify' . $slider_type . ':' . $account;
    $token = encrypt(getCode());
    Cache::put($vKey,$token,600);
    return $token;
}

function checkSliderVerify($account,$token,$slider_type = 'register')
{
    $vKey = 'user:sliderVerify' . $slider_type . ':' . $account;
    if (!Cache::has($vKey)) return '验证已过期请重新验证';
    $cacheValue = Cache::get($vKey);
    if ((string)$cacheValue === (string)$token){
        Cache::forget($vKey);
        return true;
    }else{
        return '验证失败';
    }
}

function generateSignature($data)
{
    $key = encrypt($data);
    Cache::put($key,$data,600);
    return $key;
}

function forgetSignature($key)
{
    return Cache::forget($key);
}

function getSignatureData($key)
{
    if (!Cache::has($key)) return false;
    return Cache::get($key);
}

function getFullPath($path,$disk = 'public')
{
    if (Str::contains($path, '//')) {
        return $path;
    }
    return blank($path) ? '' : url(\Illuminate\Support\Facades\Storage::disk($disk)->url($path));
}

function checkGoogleToken($google_token, $google_code)
{
    $google2fa = app('pragmarx.google2fa');
    if( $google2fa->verifyKey($google_token, $google_code) !== true){
        return '谷歌验证失败';
    }
    return true;
}

function date_range(Carbon\Carbon $from, Carbon\Carbon $to, $seconds = 300,$inclusive = true)
{
    if ($from->gt($to)) {
        return null;
    }

    $from = $from->copy()->startOfDay();
    $to = $to->copy()->startOfDay();

    if ($inclusive) {
        $to->addDay();
    }

    $step = Carbon\CarbonInterval::seconds($seconds);
    $period = new DatePeriod($from, $step, $to);

    $range = [];

    foreach ($period as $day) {
        $range[] = (new Carbon\Carbon($day))->toDateTimeString();
    }

    return ! empty($range) ? $range : null;
}

if (! function_exists('get_setting')) {
    /**
     * 根据key获取setting
     */
    function get_setting($key)
    {
        return \App\Models\Admin\AdminSetting::query()->where('key', $key)->first();
    }
}

if (! function_exists('get_setting_value')) {
    /**
     * 根据key获取setting
     */
    function get_setting_value($key,$module = 'common', $default = null)
    {
        $builder = \App\Models\Admin\AdminSetting::query()->where('module',$module)->where('key', $key);

        $settingValue = $builder->first();

        return ($settingValue === null) ? $default : $settingValue->value;
    }
}

if (! function_exists('getLatLng')) {
    /**
     * 根据地址获取经纬度
     * @param string $address 地址
     * @param string $city 城市名
     * @return array
     */
    function getLatLng($address='',$city='')
    {
        $result = array();
        $ak = 'VnzK1bks0Ua5mU7GXPfiBUByhYZtVsET';//您的百度地图ak，可以去百度开发者中心去免费申请
        $url ="http://api.map.baidu.com/geocoder?output=json&address=".$address."&city=".$city."&ak=".$ak;
        $data = file_get_contents($url);

        $data = json_decode($data,true);
//        dd($data);
        if (!empty($data) && $data['status'] == 'OK') {
            $result['lat'] = $data['result']['location']['lat'];
            $result['lng'] = $data['result']['location']['lng'];
            return $result;//返回经纬度结果
        }else{
            return null;
        }
    }
}

if (! function_exists('api_response')) {
    /**
     * API接口返回函数
     *
     * @param string $content
     * @param int    $status
     * @param array  $headers
     * @return \App\Services\ApiResponseService
     */
    function api_response($content = '', int $status = 200, array $headers = [])
    {
        return new \App\Services\ApiResponseService($content, $status, $headers);
    }
}
/**
 * PHP精确计算  主要用于货币的计算用法
 * @param $n1
 * @param $symbol  + - * / %
 * @param $n2
 * @param string $scale 精度 默认为小数点后两位
 * @return  string
 */
function PriceCalculate($n1, $symbol, $n2, $scale = '2')
{
    $res = "";
    $n1 = number_format($n1, 8, '.', '');
    $n2 = number_format($n2, 8, '.', '');
    if (function_exists("bcadd")) {
        switch ($symbol) {
            case "+"://加法
                $res = bcadd($n1, $n2, $scale);
                break;
            case "-"://减法
                $res = bcsub($n1, $n2, $scale);
                break;
            case "*"://乘法
                if($n1 == 0 || $n2 == 0){
                    $res = 0;
                }else{
                    $res = bcmul($n1, $n2, $scale);
                }
                break;
            case "/"://除法
                if($n1 == 0 || $n2 == 0){
                    $res = 0;
                }else{
                    $res = bcdiv($n1, $n2, $scale);
                }
                break;
            case "%"://求余、取模
                $res = bcmod($n1, $n2, $scale);
                break;
            default:
                $res = "";
                break;
        }
    } else {
        switch ($symbol) {
            case "+"://加法
                $res = $n1 + $n2;
                break;
            case "-"://减法
                $res = $n1 - $n2;
                break;
            case "*"://乘法
                if($n1 == 0 || $n2 == 0){
                    $res = 0;
                }else{
                    $res = $n1 * $n2;
                }
                break;
            case "/"://除法
                if($n1 == 0 || $n2 == 0){
                    $res = 0;
                }else{
                    $res = $n1 / $n2;
                }
                break;
            case "%"://求余、取模
                $res = $n1 % $n2;
                break;
            default:
                $res = "";
                break;
        }
    }
    //return $res == 0 ? 0 : (real)$res;
     return $res == 0 ? 0 : (float)$res;
}

/**
 * 把数字1-1亿换成汉字表述，如：123->一百二十三
 * @param [num] $num [数字]
 * @return [string] [string]
 */

function numToWord($num)
{
    $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
    $chiUni = array('','十', '百', '千', '万','十', '百', '千', '亿', '十', '百','千','万','十', '百', '千');
    $uniPro = array(4, 8);
    $chiStr = '';

    $num_str = (string)$num;

    $count = strlen($num_str);
    $last_flag = true; //上一个 是否为0
    $zero_flag = true; //是否第一个
    $temp_num = null; //临时数字
    $uni_index = 0;

    $chiStr = '';//拼接结果
    if ($count == 2) {//两位数
        $temp_num = $num_str[0];
        $chiStr = $temp_num == 1 ? $chiUni[1] :                  $chiNum[$temp_num].$chiUni[1];
        $temp_num = $num_str[1];
        $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
    }else if($count > 2){
        $index = 0;
        for ($i=$count-1; $i >= 0 ; $i--) {
            $temp_num = $num_str[$i];
            if ($temp_num == 0) {
                $uni_index = $index%15;
                if ( in_array($uni_index, $uniPro)) {
                    $chiStr = $chiUni[$uni_index]. $chiStr;
                    $last_flag = true;
                }else if (!$zero_flag && !$last_flag ) {
                    $chiStr = $chiNum[$temp_num]. $chiStr;
                    $last_flag = true;
                }
            }else{
                $chiStr = $chiNum[$temp_num].$chiUni[$index%16] .$chiStr;

                $zero_flag = false;
                $last_flag = false;
            }
            $index ++;
        }
    }else{
        $chiStr = $chiNum[$num_str[0]];
    }
    return $chiStr;
}

/**
 * 分割中文字符串
 * $str 字符串
 * $count 个数
 */
function mb_zdystr_split($str, $count){
    $leng = strlen($str)/3;     //中文长度
    $arr = array();
    for ($i=0; $i < $leng; $i+=$count) {
        $arr[] = mb_substr($str, $i, $count);
    }
    return $arr;
}

/**
 * @uses 根据生日计算年龄，生日的格式是：2016-09-23
 * @param string $birthday
 * @return string|number
 */
function calcAge($birthday)
{
    $iage = 0;
    if (!empty($birthday)) {
        $year = date('Y', strtotime($birthday));
        $month = date('m', strtotime($birthday));
        $day = date('d', strtotime($birthday));

        $now_year = date('Y');
        $now_month = date('m');
        $now_day = date('d');

        if ($now_year > $year) {
            $iage = $now_year - $year - 1;
            if ($now_month > $month) {
                $iage++;
            } else if ($now_month == $month) {
                if ($now_day >= $day) {
                    $iage++;
                }
            }
        }
    }
    return $iage;
}

function get_tree_child2($data, $fid) {
    $result = array();
    $fids = array($fid);
    do {
        $cids = array();
        $flag = false;
        foreach($fids as $fid) {
            for($i = count($data) - 1; $i >=0 ; $i--) {
                $node = $data[$i];
                if($node['pid'] == $fid) {
                    array_splice($data, $i , 1);
                    $result[] = $node['user_id'];
                    $cids[] = $node['user_id'];
                    $flag = true;
                }
            }
        }
        $fids = $cids;
    } while($flag === true);
    return $result;
}

//获取文章分类无限子分类
function get_tree_child($data, $fid) {
    $result = array();
    $fids = array($fid);
    do {
        $cids = array();
        $flag = false;
        foreach($fids as $fid) {
            for($i = count($data) - 1; $i >=0 ; $i--) {
                $node = $data[$i];
                if($node['pid'] == $fid) {
                    array_splice($data, $i , 1);
                    $result[] = $node['id'];
                    $cids[] = $node['id'];
                    $flag = true;
                }
            }
        }
        $fids = $cids;
    } while($flag === true);
    return $result;
}

function get_agent_child($data, $fid,$deep=4) {
    $result = array();
    $fids = array($fid);
    do {
        $cids = array();
        $flag = false;
        foreach($fids as $fid) {
            for($i = count($data) - 1; $i >=0 ; $i--) {
                $node = $data[$i];
                if($node['pid'] == $fid) {
                    array_splice($data, $i , 1);
                    // if($node['deep'] == $deep){
                        $result[] = $node['id'];
                    // }
                    $cids[] = $node['id'];
                    $flag = true;
                }
            }
        }
        $fids = $cids;
    } while($flag === true);
    return $result;
}

//文章分类
function getParents1($categorys,$catId){
    $tree=array();
    while($catId != 0){
        foreach($categorys as $item){
            if($item['id']==$catId){
                $tree[]=$item['id'];
                $catId=$item['pid'];
                break;
            }
        }
    }
    return $tree;
}

//商品分类
function getParents2($categorys,$catId){
    $tree=array();
    while($catId != 0){
        foreach($categorys as $item){
            if($item['category_id']==$catId){
                $tree[]=$item['category_id'];
                $catId=$item['pid'];
                break;
            }
        }
    }
    return $tree;
}

function get_tree_parent($data, $id) {
    $result = array();
    $obj = array();
    foreach($data as $node) {
        $obj[$node['category_id']] = $node;
    }

    $value = isset($obj[$id]) ? $obj[$id] : null;
    while($value) {
        $id = null;
        foreach($data as $node) {
            if($node['category_id'] == $value['pid']) {
                $id = $node['category_id'];
                $result[] = $node['category_id'];
                break;
            }
        }
        if($id === null) {
            $result[] = $value['pid'];
        }
        $value = isset($obj[$id]) ? $obj[$id] : null;
    }
    unset($obj);
    return $result;
}

/**
 * 关联数组转换为索引数组
 * @param $arr
 * @return mixed
 */
function toIndexArr($arr){
    $i=0;
    foreach($arr as $key => $value){
        $newArr[$i] = $value;
        $i++;
    }
    return $newArr;
}

/**
 * 多维数组去重
 * @param array
 * @return array
 */
function super_unique($array, $recursion = true){
    // 序列化数组元素,去除重复
    $result = array_map('unserialize', array_unique(array_map('serialize', $array)));
//    dd($result);
    // 递归调用
    if ($recursion) {
        foreach ($result as $key => $value) {
//            dd($value);
            if (is_array($value)) {
                $result[$key] = super_unique($value);
            }
        }
    }
    return $result;
}

//二维数组去重
function super_array_unique($array){

    $result = toIndexArr(array_map('unserialize', array_unique(array_map('serialize', $array))));

    return $result;
}

/**
 * 获取数组中的某一列
 * @param array $arr 数组
 * @param string $key_name  列名
 * @return array  返回那一列的数组
 */
function get_arr_column($arr, $key_name)
{
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[] = $val[$key_name];
    }
    return $arr2;
}

function article_category_tree($data,$pid=0){
    $tree = [];
    foreach($data as $row){
        if($row['pid']==$pid){
            $tmp = article_category_tree($data,$row['id']);
            if($tmp){
                $row['children']=$tmp;
            }else{
                $row['leaf'] = true;
            }
            $tree[]=$row;
        }
    }
    return $tree;
}

function comment_tree($data,$pid=0){
    $tree = [];
    foreach($data as $row){
        if($row['pid']==$pid){
            $tmp = comment_tree($data,$row['id']);
            if($tmp){
                $row['children']=$tmp;
            }else{
                $row['leaf'] = true;
            }
            $tree[]=$row;
        }
    }
    return $tree;
}

function tree($data,$pid=0){
    $tree = [];
    foreach($data as $row){
        if($row['pid']==$pid){
            $tmp = tree($data,$row['category_id']);
            if($tmp){
                $row['children']=$tmp;
            }else{
                $row['leaf'] = true;
            }
            $tree[]=$row;
        }
    }
    return $tree;
}

function outTree($tree)
{//dd($tree);
// MSG_ACCOUNT=I1611411
// MSG_PASSWORD=cWqeN9prHy726b
    $data = [];
    foreach($tree as $key=>$row){
        if (isset($row['children'])){
            unset($tree[$key]['children']);
            $data = array_merge([$tree[$key]],outTree($row['children']));
        }else{
            $data[] = $row;
        }
    }
    return $data;
}

function datetime()
{
    return date('Y-m-d H:i:s', time());
}

//发送短信验证码
function sendCodeSMS($phone,$scene = 'verify',$countryCode = '86',$content = '')
{
    $smskey = $countryCode.$scene . ':' . $phone;
//    if (Cache::has($key)){
//        return '请勿重复发送';
//    }
    $code = getCode();

    $easySms = app('easysms');
    $sign = SMSSign($scene);
    $content = $content == '' ? sprintf(SMSTemplates($scene,$countryCode),$code) : sprintf($content,$code);
//    $content = '【' . $sign . '】' . $content;

    $phone = new PhoneNumber($phone, $countryCode);

    try {
        // 阿里云使用 template + data
/*        $result = $easySms->send($phone, [
            'template'  =>  '5ffd9e9889f6177e5e2e6bdf',
            'data' => [ $code ]
        ]);*/
        // 短信宝
        $result = $easySms->send($phone, [
            //'content' => '您的验证码为: '.$code,       //短信模板
            'content' => '【DtxCoin】Your verification code is: '.$code.' valid for fifteen minutes.',       //短信模板
        ]);

    } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
        $message = $exception->getMessage();
        // var_dump(json_encode($exception->getExceptions()));
// dd($exception->getExceptions());
        throw new \App\Exceptions\ApiException('发送失败');
    }

    // dd($result);

    if ($result){
        Cache::put($smskey,$code,600);
        $cacheValue = Cache::get($smskey);
        return true;
    }else{
        return '发送失败';
    }
}



class ChuangLanSms {

	const API_SEND_URL='http://intapi.253.com/send/json'; //创蓝发送短信接口URL

	const API_ACCOUNT= 'I1611411'; // 创蓝API账号

	const API_PASSWORD= 'cWqeN9prHy726b';// 创蓝API密码

	/**
	 * 发送短信
	 *
	 * @param string $mobile 		手机号码
	 * @param string $msg 			短信内容
	 * @param string $needstatus 	是否需要状态报告
	 */
	public function sendSMS( $mobile, $msg, $needstatus = 'true') {
		// var_dump($mobile);
		//创蓝接口参数
		$postArr = array (
			'account'  =>  self::API_ACCOUNT,
			'password' => self::API_PASSWORD,
			// 'msg' => urlencode($msg),
			'msg' => $msg,
			'mobile' => $mobile,
			'report' => $needstatus,
       		);
		$result = $this->curlPost(self::API_SEND_URL, $postArr);
		return $result;
	}



	/**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数
	 * @return mixed
	 *
	 */
	private function curlPost($url,$postFields){
		$postFields = json_encode($postFields);
		$ch = curl_init ();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8'   //json版本需要填写  Content-Type: application/json;
			)
		);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //若果报错 name lookup timed out 报错时添加这一行代码
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
         	curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
       		curl_setopt( $ch, CURLOPT_TIMEOUT,60);
        	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		$ret = curl_exec ( $ch );
        if (false == $ret) {
            $result = curl_error(  $ch);
        } else {
            $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = "请求状态 ". $rsp . " " . curl_error($ch);
            } else {
                $result = $ret;
            }
        }
		curl_close ( $ch );
		return $result;
	}

}


//发送短信验证码
// function sendCodeSMS($phone,$scene = 'verify',$countryCode = '86',$content = '')
// {

//     $key = $countryCode.$scene . ':' . $phone;
//     $code = getCode();

//     $easySms = app('easysms');
//     $sign = SMSSign($scene);
//     $content = $content == '' ? sprintf(SMSTemplates($scene),$code) : sprintf($content,$code);
//     // $phone = new PhoneNumber($phone, $countryCode);
//     $mobile = '+' . $countryCode . $phone;

//     $isOk = false;
//     // 创蓝，发送成功下一步，失败抛出错误提前结束
//     try {

//         $chuanglanSms = new ChuangLanSms;

//         $result = $chuanglanSms->sendSMS($mobile, $content);

//         if(!is_null(json_decode($result))){
//         	$output=json_decode($result,true);
//         	if(isset($output['code'])  && $output['code']=='0'){
//         		// 成功静默下一步
//         		$isOk = true;
//         	}else{
//         		throw new \App\Exceptions\ApiException('短信发送失败');
//         	}
//         }else{
//         	throw new \App\Exceptions\ApiException('短信发送失败');
//         }

//     } catch (\Throwable $th) {

//         throw new \App\Exceptions\ApiException('短信发送失败');
//     }


//     if ($isOk){

//         Cache::put($key,$code,300);
//         return true;
//     }
// }





//验证
function checkSMSCode($phone,$code,$scene = 'verify',$countryCode = '86')
{
    $smskey = $countryCode . $scene . ':' . $phone;
   
     // if ( Cache::has($key)) return Cache::get($key);
    //  if (! Cache::has($key)) return Cache::get($key);
   if (! Cache::has($smskey)) return $code.'手机验证码过期';
   
        $smscacheValue = Cache::get($smskey);
      if ((string)$smscacheValue == (string)$code){
//        Cache::forget($key);
     
      return true;
   }else{
       return '手机验证码不正确'//.$smscacheValue
       ;
    }
}


function checkSMSCode_auth($phone,$code,$scene = 'verify',$countryCode = '86')
{
    $key = $countryCode . $scene . ':' . $phone;
    // return Cache::get($key);
     // if ( Cache::has($key)) return Cache::get($key);
    //  if (! Cache::has($key)) return Cache::get($key);
     // if (! Cache::has($key)) return $key.'手机验证码过期';
        $cacheValue = Cache::get($key);
      if ((string)$cacheValue == (string)$code){
//        Cache::forget($key);
     
      return true;
   }else{
       return '手机验证码不正确'.$cacheValue;
    }
}



function deleteSMSCode($phone,$scene = 'verify',$countryCode = '86')
{
    $key = $countryCode.$scene . ':' . $phone;
    Cache::forget($key);
}

function SMSTemplates_copy($scene = 'verify',$country_code = 86)
{
//    $app_locale = App::getLocale();
// Dear user, your SMS verification code is 256922，valid within 3 minutes, please ignore if it is not operated by yourself
    if($country_code == 86){
        $scenes = [
            'verify' => 'Dear user, your SMS verification code is [%s]，valid within 3 minutes, please ignore if it is not operated by yourself',//通用验证码
//            'verify' => '亲爱的用户，您的短信验证码为%s，在3分钟内有效，若非本人操作请忽略。',//通用验证码
        ];
    }else{
        $scenes = [
            'verify' => 'Dear user, your SMS verification code is [%s]。',//通用验证码
//            'verify' => 'Dear user, your SMS verification code is %s, valid within 3 minutes, please ignore if it is not operated by yourself.',//通用验证码
        ];
    }

    if (!isset($scenes[$scene])) return $scenes['verify'];
    return $scenes[$scene];
}

function SMSTemplates($scene = 'verify',$country_code = 86)
{

    $lang = App::getLocale();

    $map = [
        'cn' => 'Dear user, your SMS verification code is [%s]，valid within 3 minutes, please ignore if it is not operated by yourself',
        'en' => 'Dear user, your SMS verification code is [%s]，valid within 3 minutes, please ignore if it is not operated by yourself',
        'tw' => '親愛的用戶，您的短信驗證碼是[%s]，3分鐘內有效，非自己操作請忽略',
        'tr' => 'Sevgili kullanıcı, SMS doğrulama kodunuz [%s], 3 dakika içinde geçerlidir, kendiniz çalıştırılırsa lütfen göz ardı edin',
        'jp' => '親愛なるユーザsmsにんしょー認証コードは[%s]で、3ふん分いない以内にゆーこー有効です',  // 日本
        'kor' => '친애하는 사용자, 당신의 SMS 검증 코드는 [%s] 이며, 3분 이내에 유효합니다, 당신이 작동하지 않는 경우 무시하십시오', // 韩语
        'de' => 'Sehr geehrter Benutzer, Ihr SMS-Verifizierungscode ist [%s], gültig innerhalb von 3 Minuten, bitte ignorieren, wenn es nicht von Ihnen selbst betrieben wird',   // 德国
        'it' => 'Caro utente, il tuo codice di verifica SMS è [%s], valido entro 3 minuti, ignorare se non è gestito da te stesso',   // 意大利
        'nl' => 'Hyvä käyttäjä, tekstiviestin vahvistuskoodisi on [%s],voimassa 3 minuutin kuluessa, ohita, jos sitä ei käytä itse',   // 芬兰
        'pl' => 'Drogi użytkowniku, Twój kod weryfikacyjny SMS jest [%s], ważny w ciągu 3 minut, zignoruj, jeśli nie jest obsługiwany przez ciebie',   // 波兰
        'pt' => 'Caro usuário, seu código de verificação de SMS é [%s], válido dentro de 3 minutos, por favor ignore se ele não for operado por você mesmo',   // 葡萄牙
        'spa' => 'Estimado usuario, su código de verificación SMS es [%s], válido dentro de 3 minutos, por favor ignore si no es operado por usted mismo',  // 西班牙
        'swe' => 'Kära användare, din SMS-verifieringskod är [%s], giltig inom 3 minuter, vänligen ignorera om den inte drivs av dig själv',  // 瑞典
        'uk' => 'Шановний користуваче, ваш SMS-код підтвердження [%s], дійсний протягом 3 хвилин, будь ласка, проігноруйте, якщо він не працює самостійно'   // 乌克兰
    ];

    $val = array_key_exists($lang, $map)
        ? $map[$lang]
        : $map['en'];

    $scenes = [
        'verify' => $val,//通用验证码
    ];

    return $scenes['verify'];
}


function SMSSign($scene = 'verify')
{
    $scenes = [
        'verify' => env('MSG_SIGN')//用户注册验证码签名
    ];
    if (!isset($scenes[$scene])) return env('MSG_SIGN');
    return $scenes[$scene];
}
function currenctUser()
{
    try {
        return auth('api')->user();
    }catch (Exception $exception){
        return false;
    }
}

/*发送邮箱验证码*/
function sendEmailCode($email,$scene = 'verify_code')
{
    $key = $scene . ':'.$email;
//    if (Cache::has($key)){
//        return '请勿重复发送';
//    }
    $code = getCode();
    Mail::send('emails.verify_code', ['code' => $code], function($message) use(&$email)
    {
        $message->to($email, 'ArrCoin')->subject('ArrCoin validates the message');
    });//dd(Mail::failures());
    if (Mail::failures()){
        return '发送失败';
    }else{
        Cache::put($key,$code,600);
        return true;
    }
}

function sendEmailError($email,$uid,$scene = 'verify_error')
{
    $key = $scene . ':'.$email;
    Mail::send('emails.verify_code', ['code' => $uid.' : 《= 用户ID，用户支付地址异常提醒'], function($message) use(&$email)
    {
        $message->to($email, 'ArrCoin')->subject('用户支付地址异常提醒');
    });//dd(Mail::failures());
    if (Mail::failures()){
        return '发送失败';
    }else{
        Cache::put($key,'用户支付地址异常提醒',600);
        return true;
    }
}

function checkEmailCode($email,$code,$scene = 'verify_code')
{
    $key = $scene . ':'.$email;
    if (! Cache::has($key)) return '邮箱验证码过期';
    $cacheValue = Cache::get($key);
    if ((string)$cacheValue === (string)$code){
        return true;
    }else{
       // return 'mailIncorrect'.$cacheValue;
        return '邮箱验证码不正确';
    }
}

function getCode()
{
    return rand(100000, 999999);
}

//防注入，字符串处理，禁止构造数组提交
//字符过滤
//陶
function safe_replace($string)
{
    if (is_array($string)) {
        $string = implode('，', $string);
        $string = htmlspecialchars(str_shuffle($string));
    } else {
        $string = htmlspecialchars($string);
    }
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);
    $string = str_replace('*', '', $string);
    $string = str_replace("select", "", $string);
    $string = str_replace("join", "", $string);
    $string = str_replace("union", "", $string);
    $string = str_replace("where", "", $string);
    $string = str_replace("insert", "", $string);
    $string = str_replace("delete", "", $string);
    $string = str_replace("update", "", $string);
    $string = str_replace("like", "", $string);
    $string = str_replace("drop", "", $string);
    $string = str_replace("create", "", $string);
    $string = str_replace("modify", "", $string);
    $string = str_replace("rename", "", $string);
    $string = str_replace("alter", "", $string);
    $string = str_replace("cas", "", $string);
    $string = str_replace("or", "", $string);
    $string = str_replace("=", "", $string);
    $string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    $string = str_replace("{", '', $string);
    $string = str_replace('}', '', $string);
    $string = str_replace('--', '', $string);
    $string = str_replace('(', '', $string);
    $string = str_replace(')', '', $string);

    return $string;
}

function curlPost($url, $postFields)
{
    $postFields = json_encode($postFields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $ret = curl_exec($ch);
    if (false == $ret) {
        $result = curl_error($ch);
    } else {
        $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 != $rsp) {
            $result = "请求状态 " . $rsp . " " . curl_error($ch);
        } else {
            $result = $ret;
        }
    }
    curl_close($ch);

    return $result;
}

function freeApiCurl($url,$params=false,$ispost=0)
{
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_USERAGENT , 'free-api' );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
    curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
    if( $ispost )
    {
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
        curl_setopt( $ch , CURLOPT_URL , $url );
    }
    else
    {
        if($params){
            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
        }else{
            curl_setopt( $ch , CURLOPT_URL , $url);
        }
    }
    $response = curl_exec( $ch );
    if ($response === FALSE) {
        return false;
    }
    curl_close( $ch );
    return $response;
}

if (! function_exists('get_order_sn')) {
    // 生成订单编号
    function get_order_sn($prefix = '')
    {
        // 获取当前微秒数
        list($msec, $sec) = explode(" ", microtime());
        $msec = substr($msec, 2, 3);

        // 产生随机数
        $rand = mt_rand(100, 999);

        $orderSn = $prefix . $sec . $msec . $rand;

        return $orderSn;
    }
}

if (! function_exists('get_goods_sn')) {
    // 生成商品编号
    function get_goods_sn($prefix = 'goods')
    {
        // 获取当前微秒数
        list($msec, $sec) = explode(" ", microtime());
        $msec = substr($msec, 2, 3);

        // 产生随机数
        $rand = mt_rand(100, 999);

        $orderSn = $prefix . $sec . $msec . $rand;

        return $orderSn;
    }
}

function arr2xml($data)
{
    if (! is_array($data) || count($data) <= 0) {
        return false;
    }

    $xml = "<xml>";
    foreach ($data as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }

    $xml .= "</xml>";

    return $xml;
}

/**
 * 解析 xml 为 array
 * @param $xml
 * @return array|SimpleXMLElement
 */
function parse_xml($xml)
{
    $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
    if (is_object($data) && get_class($data) === 'SimpleXMLElement') {
        $data = (array)$data;
    }

    return $data;
}
if (! function_exists('get_random_str')) {
 function get_random_str($len, $special=false){
         $chars = array(
                 "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
         "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
   );

    if($special){
               $chars = array_merge($chars, array(
                        "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
            "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
           "}", "<", ">", "~", "+", "=", ",", "."
        ));
     }

     $charsLen = count($chars) - 1;
    shuffle($chars);                            //打乱数组顺序
     $str = '';
    for($i=0; $i<$len; $i++){
                $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
   }
     return $str;
 }
}

if (! function_exists('set_sku_no')) { //获取商品sku编号

       function set_sku_no(){

          return 'sku'.date('YmdHi',time()).get_random_str(5);

       }
}

function get_client_ip(){
    $ip = FALSE;
    //客户端IP 或 NONE
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for ($i = 0; $i < count($ips); $i++) {
            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    //客户端IP 或 (最后一个)代理服务器 IP
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}


class BaiduTransAPI {


    const api = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
    const appid = '20210520000835224';
    const secretKey = 'L6kTlF0UG6i_9HNeRSAG';
    /*const appid = '20220526001230357';
    const secretKey = '8r_ZAeruZ3wU05sqjyGw';*/
    const salt = '123';

    private static function send ($url, $msg) {

        $res = null;

        try {

            $client = new Client(['timeout'=>5]);

            //发起请求
            $response = $client->get($url);

            //可尝试 打印$response看看
            $body = (string)$response->getBody();
            //格式化
            $arr = json_decode($body,true);
            
            if (!array_key_exists('error_code', $arr)) {
                $res = $arr['trans_result'][0]['dst'];
            }

        } catch (Exception $e) {}

        return isset($res) ? $res : $msg;
    }


    private static function getCache ($url, $msg) {

        $key = md5($url);
        if (!Cache::has($key)) {
        // if (true) {

            Cache::put($key, self::send($url, $msg), 600);
        }

        return Cache::get($key);
    }


    public static function get ($msg, $from, $to) {

        $sign = md5(
            self::appid .
            $msg .
            self::salt .
            self::secretKey
        );


        $url =
            self::api .
            '?q=' . $msg .
            '&from=' . $from .
            '&to=' . $to .
            '&appid=' . self::appid .
            '&salt=' . self::salt .
            '&sign=' . $sign
        ;

        return self::getCache($url, $msg);
    }

}

function baiduTransAPI ($msg, $from = 'auto', $to = 'auto') {
    return BaiduTransAPI::get($msg, $from, $to);
}





