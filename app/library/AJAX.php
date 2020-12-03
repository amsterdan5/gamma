<?php
declare (strict_types = 1);
/**
 * 统一AJAX返回的格式
 */
class AJAX extends \Phalcon\DI\Injectable
{
    const SUCCESS         = 200; // 成功码
    const ERROR           = 201; // 通用错误码
    const AUTH_FAILED     = 401; // 无权限
    const FORBIDDEN       = 403; // 禁止访问
    const INVALID_REQUEST = 404; // 无效请求
    const INTERNAL_ERROR  = 500; // 内部错误

    /**
     * AJAX 响应数据 (JSON格式)
     *
     * @param integer $code    响应代码
     * @param string  $message 响应消息
     * @param mixed   $data    响应数据
     */
    public function response($code, $message = null, $data = null)
    {
        $this->response
            ->setJsonContent([
                'code'      => (int) $code,
                'message'   => $message,
                'data'      => $data,
                'timestamp' => time(),
            ], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE)
            ->send();
        exit;
    }

    /**
     * 响应错误信息
     *
     * @param string $message
     * @param array  $data
     */
    public function error($message = null, $data = null)
    {
        $this->response(self::INTERNAL_ERROR, $message, $data);
    }

    /**
     * 响应成功信息
     *
     * @param string $message
     * @param array  $data
     */
    public function success($message = null, $data = null)
    {
        $this->response(self::SUCCEED, $message, $data);
    }
}
