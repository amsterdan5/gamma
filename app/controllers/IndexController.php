<?php
declare (strict_types = 1);

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        echo 'ok';
        $this->view->title = '去水印';
    }
}
