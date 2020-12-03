<?php
declare (strict_types = 1);

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    /**
     * 默认语言类型
     * @var string
     */
    public $lang = 'zh-cn';

    // Implement common logic
    public function beforeExecuteRoute($dispatcher)
    {
        $this->lang = $this->i18n->getDefault();

        // 自动加载语言包
        $this->i18n->import([
            uncamel($dispatcher->getControllerName()),
            uncamel($dispatcher->getControllerName()) . '/' . uncamel($dispatcher->getActionName()),
        ]);

        $this->view->setVars([
            'title'       => 'watermark',
            'update_time' => app_time(),
        ]);
        return true; // false 停止执行
    }
}
