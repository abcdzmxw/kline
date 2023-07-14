<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Response;

class ApiResponseService extends Response
{
    public function __construct(string $content = '', int $status = 200, array $headers = array())
    {
        parent::__construct($content, $status, $headers);
    }

    /**
     * 业务成功
     *
     * @param null $data
     * @param null $message
     * @return $this
     */
    public function success($message = null,$data = null)
    {
        $data = collect($data)->toArray();
        $data = $this->withoutWrapping($data);

        $response = [
            'data' => $data,
            'code' => 200,
            'result_code' => 'SUCCESS',
            'message' => blank($message) ? 'OK' : __($message),
        ];

        $this->setContent($response);

        return $this;
    }

    public function successString($message = null,$data = null)
    {
        $response = [
            'data' => $data,
            'code' => 200,
            'result_code' => 'SUCCESS',
            'message' => blank($message) ? 'OK' : __($message),
        ];

        $this->setContent($response);

        return $this;
    }

    /**
     * 去除可能的外层data键值包裹
     *
     * @param $data
     * @return mixed
     */
    private function withoutWrapping($data)
    {
        if (count($data) == 1 && isset($data['data']) && is_array($data['data'])) {
            return $this->withoutWrapping($data['data']);
        }

        return $data;
    }

    /**
     * 业务失败
     *
     * @param     $message
     * @param int $code
     * @return $this
     */
    public function error($code = 0, $message = null , Exception $e = null)
    {
        $response = [
            'code' => is_numeric($code) ? $code : 0,
            'result_code' => 'FAIL',
        ];

        if (! empty($message) && is_string($message)) {
            $response['message'] = __($message);
        } elseif (! empty($e)) {
            $response['message'] = $e->getMessage();
        } else {
            $response['message'] = __('系统发生错误，请稍后再试');
        }

        $debug = request('_debug');

        if (config('app.debug') === true && $debug === 'true') {
            if (! empty($e)) {
                $response['debug'] = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            }
        }

        $this->setContent($response)->setStatusCode(200);

        return $this;
    }

}
