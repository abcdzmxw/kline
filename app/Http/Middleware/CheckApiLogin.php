<?php

namespace App\Http\Middleware;

use App\Events\UserUpgradeEvent;
use App\Exceptions\ApiException;
use Closure;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class CheckApiLogin extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // 检查此次请求中是否带有 token，如果没有则抛出异常。
            $this->checkForToken($request);
        } catch(\Exception $e) {
            return api_response()->error(1003,__('未登录'));
        }

        try {
            if($this->auth->parseToken()->check() === false){
                return api_response()->error(1003,'验证Token已过期，请重新登录');
            }
            $login_code = auth('api')->payload()->get('login_code');
            $user = $this->auth->parseToken()->authenticate();

            //单设备登陆
//            if ($user && $login_code !== $user->login_code) {
//                return api_response()->error(1003,'你的账号在其他地方登录，你被迫下线');
//            }
        } catch (TokenExpiredException $e) {
            //Token过期
            try {
                // 刷新用户的 token
                $token = auth('api')->refresh();
                // 使用一次性登录以保证此次请求的成功
                $user_id = auth('api')->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub'];

                auth('api')->onceUsingId($user_id);
                $GLOBALS['refreshToken'] = $token;

                // 在响应头中返回新的 token
                return $this->setAuthenticationHeader($next($request), $token);
            } catch (JWTException $exception) {
                // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
                return api_response()->error(1003,'验证Token已过期，请重新登录');
            }

        } catch (JWTException $e) {
            return api_response()->error(1003,'验证Token已过期，请重新登录');
        } catch (TokenBlacklistedException $e){//黑名单异常
            return api_response()->error(1003,'验证Token已过期，请重新登录');
        }catch (TokenInvalidException $e){
            return api_response()->error(1003,'Token无效');
        }

        return $next($request);
    }
}
