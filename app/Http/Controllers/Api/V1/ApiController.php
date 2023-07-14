<?php
namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\FileTools;
use App\Traits\RedisTool;
use App\Traits\Tools;

class ApiController extends Controller
{
    use FileTools,Tools,RedisTool,ApiResponse;

//    public function __construct()
//    {
//        $this->middleware(function ($request, $next) {
//            $this->request = $request;
//
//            if (auth('api')->check()) {
//                $this->user = $this->current_user();
//            }
//
//            return $next($request);
//        });
//    }

    public function current_user()
    {
        $user = auth('api')->user();
        if(blank($user)){
            return null;
        }else{
            if ($user->status == User::user_status_freeze) {
                throw new ApiException('账号被冻结，请联系客服');
            }
            return $user;
        }
    }

}
