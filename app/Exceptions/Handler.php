<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof TokenExpiredException) {
            $msg = __('验证Token已过期，请重新登录');
            return api_response()->error(-1, $msg, $exception);
        } else if ($exception instanceof TokenInvalidException) {
            $msg = __('非法的Token');
            return api_response()->error(-1, $msg, $exception);
        } else if ($exception instanceof JWTException) {
            $msg = __('非法的Token');
            return api_response()->error(-1,$msg, $exception);
        } else if ($exception instanceof ModelNotFoundException) {
            $msg = __('模型被UFO略走了O_O');
            return api_response()->error(0,$msg, $exception);
        } else if ($exception instanceof ValidationException) {
            return api_response()->error(0, $exception->validator->errors()->first(),$exception);
        } else if ($exception instanceof NotFoundHttpException) {
          //  $msg = __('资源被火星人略走了>_<');
              $msg = __('coming_soon>_<');
            return api_response()->error(0, $msg, $exception);
        } else if ($exception instanceof MethodNotAllowedHttpException) {
            $msg = __('非法请求');
            return api_response()->error(0, $msg, $exception);
        } else if ($exception instanceof UserNotDefinedException) {
            $msg = __('用户被外星人略走了');
            return api_response()->error(0, $msg,$exception);
        } elseif ($exception instanceof AuthenticationException) {
            $msg = __('需要验证Token');
            return api_response()->error(0, $msg, $exception);
        }
          elseif ($exception instanceof NotFoundHttpException) {
            $msg = __('finish primary certification');
            return api_response()->error(0, $msg, $exception);
        }

        return parent::render($request, $exception);
    }
}
