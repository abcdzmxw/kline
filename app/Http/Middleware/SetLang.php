<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

class SetLang
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle2($request, Closure $next)
    {
        $user_locale = $request->header("lang");
//        dd($user_locale);
        if(empty($user_locale) || !in_array($user_locale, ['id','cn', 'en' ,'tw','tr'])) {
//           $user_locale = 'en';
            $user_locale = 'en';
        }
        if($user_locale == 'cn') $user_locale = 'zh-CN';
        if($user_locale == 'tw') $user_locale = 'zh-TW';
        $app_locale = App::getLocale();
//        dd($app_locale);
        if($app_locale !==  $user_locale) {
            App::setLocale($user_locale);
        }
        // dd(App::getLocale());
        return $next($request);
    }
    
    private static function useQuery ($request) {
        
        $uri = $request->path();
        
        $map = [
            'api/app/exchange/getCoinInfo',
            'api/exchange/getCoinInfo',
            'api/app/article/list',
            'api/article/list',
            'api/app/user/logout',
            'api/user/logout'
        ];
        if (!in_array($uri, $map)) return $request->header("lang");
        
        if (!isset($request->query()['lang'])) return $request->header("lang");
        
        return $request->query()['lang'];
    }

    public function handle($request, Closure $next)
    {
         $user_locale = $request->header("lang");
         $user_locale = self::useQuery($request);

        // 语言缩写
        $suoxie = [
          //  'id',
            'cn',
            'en',
            'tw',
            'tr',
            'jp',  // 日本
            'kor', // 韩语
            'de',   // 德国
            'it',   // 意大利
            'nl',   // 芬兰
            'pl',   // 波兰
            'pt',   // 葡萄牙
            'spa',  // 西班牙
            'swe',  // 瑞典
            'uk',    // 乌克兰
            'fin',  //  芬兰
            'fra',   //法国
            'zh-CN',
            'zh-TW'
        ];
        if(empty($user_locale) || !in_array($user_locale, $suoxie)) {

            // $user_locale = 'zh-CN';
           // $user_locale = 'en';
           $user_locale = 'zh-CN';
        }
        if($user_locale == 'cn') $user_locale = 'zh-CN';
        if($user_locale == 'tw') $user_locale = 'zh-TW';
        if($user_locale == 'id') $user_locale = 'id';
        $app_locale = App::getLocale();

        // var_dump($app_locale);
        if($app_locale !==  $user_locale) {
            App::setLocale($user_locale);
        }
        
        return $next($request);
    }

}















