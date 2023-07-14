<?php

namespace App\Traits;


trait ApiResponse
{
    //参数错误
    public function parameterError($code = 1004,$message = '参数错误' , $data = null) {
        return $this->responseJson($code,$message,$data);
    }

    //处理成功
    public function success($message = 'success',$data = null) {
        return $this->responseJson(200,$message,$data);
    }

    public function successWithData($data = null,$message = 'success') {
        return $this->responseJson($code = 200,$message,$data);
    }

    public function error($code = 4001,$message = 'fail',$data = null) {
        return $this->responseJson($code,$message,$data);
    }

    public function responseJson($statusCode,$message,$data)

    {
        if (isset($GLOBALS['refreshToken']))
        return response()->json([ 'code' => $statusCode, 'message' => __($message),'data' => $data, 'refresh_token'=>$GLOBALS['refreshToken']]);
        return response()->json([ 'code' => $statusCode, 'message' => __($message),'data' => $data]);
    }

}
