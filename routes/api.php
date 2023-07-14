<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

$api = app('Dingo\Api\Routing\Router');

$webapiParams = [
    'version' => 'v1',
    'prefix' => 'api',
//    'domain' => env('APP_DOMAIN'),
    'namespace' => 'App\\Http\\Controllers\\Api',
];

$appapiParams = [
    'version' => 'v1',
    'prefix' => 'api/app',
 //   'prefix' => 'appapi',
//    'domain' => env('APP_DOMAIN'),
    'namespace' => 'App\\Http\\Controllers\\Appapi',
];

// Web端接口
$api->group($webapiParams, function ($api) {
    require_once "yx_api.php";
    require_once "cwp_api.php";
    require_once "yjt_api.php";
});

// 移动端接口
$api->group($appapiParams, function ($api) {
    require_once "yx_appapi.php";
    require_once "cwp_appapi.php";
    require_once "yjt_appapi.php";
});
