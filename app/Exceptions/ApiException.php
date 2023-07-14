<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/25
 * Time: 14:16
 */

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception
{
    protected $code;
    protected $message;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $this->code = $code;
        $this->message = $message;
        parent::__construct($message, $code, $previous);
    }

    public function report()
    {
        //
    }

    public function render($request)
    {
        return response()->json(['code'=>$this->code,'message'=>__($this->message)]);
    }

}
