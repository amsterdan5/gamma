<?php
declare (strict_types = 1);

class ErrorController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        // 使用 action 视图
        // 防止被禁止输出视图
        $this->view->setRenderLevel(Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

        $exception = $this->dispatcher->getParam('exception');

        // 防止被外部调用时出错
        if (!$exception instanceof Exception) {
            $exception = new Phalcon\Exception('Invalid request', 404);
        }

        $this->view->exception = $exception;

        switch (true) {
            // 401 UNAUTHORIZED
            case ($exception->getCode() == 401):
                $code  = 401;
                $title = '401 Unauthorized';
                break;

            // 403 FORBIDDEN
            case ($exception->getCode() == 403):
                $code  = 403;
                $title = '403 Forbidden';
                break;

            // 404 NOT FOUND
            case ($exception->getCode() == 404):
            case ($exception instanceof Phalcon\Mvc\View\Exception):
            case ($exception instanceof Phalcon\Mvc\Dispatcher\Exception):
                $code  = 404;
                $title = '404 Not Found';
                break;

            // 500 INTERNAL SERVER ERROR
            default:
                $code  = 500;
                $title = '500 Internal Server Error';
        }

        // AJAX 输出
        if ($this->request->isAjax()) {
            return $this->ajax->response($code, $exception->getMessage());
        }

        $this->view->code        = $code;
        $this->view->title       = $title;
        $this->view->update_time = app_time();
    }
}
