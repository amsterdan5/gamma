<?php
declare (strict_types = 1);

class HelpController extends ControllerBase
{
    public function pageAction()
    {
        $name = $this->request->get('name');
        $id   = $this->request->get('id');
        var_dump($name, $id);
    }

}
