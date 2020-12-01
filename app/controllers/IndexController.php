<?php
declare(strict_types=1);

use \User;
use \House;

class IndexController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function getAction()
    {
        $user = new User();
        $user->name = 'hello';
        $r = $user->save();
        if ($r) {
            echo 'ok';
        } else {
            echo 'no';
        }
    }
}

