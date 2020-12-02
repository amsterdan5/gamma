<?php
declare (strict_types = 1);

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    // Implement common logic
    public function beforeExecuteRoute($dispatcher)
    {
        $this->view->setVars([
            'update_time' => app_time(),
        ]);
    }
}
