<?php
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

/**
 * 异常处理
 */
class ExceptionsPlugin extends Injectable
{
    public function beforeException(Event $event, Dispatcher $dispatcher, $exception)
    {
        $dispatcher->setParam('exception', $exception);

        // 错误信息
        $message = get_class($exception) . ': '
        . maskroot($exception->getMessage())
        . ' (in ' . maskroot($exception->getFile())
        . ' on line ' . $exception->getLine() . ')' . "\n" .
        maskroot($exception->getTraceAsString()) . "\n";

        // 记录日志
        logger('errors', $message, Phalcon\Logger::ERROR);

        // 非页面输出
        if (IS_AJAX || IS_CURL || IS_CLI) {
            $this->response
                ->setContentType('text/plain', 'utf-8')
                ->setContent(!PRODUCTION ? $message : '内部错误')
                ->send();
            exit;
        }

        $this->view->setViewsDir(APP_PATH . '/views');
        $forward = [
            'namespace'  => '\\',
            'controller' => 'error',
            'action'     => 'index',
        ];

        $dispatcher->forward($forward);
        return false;
    }
}
