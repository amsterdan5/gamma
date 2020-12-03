<?php
declare (strict_types = 1);

use External\Douyin;

class IndexController extends ControllerBase
{

    public function indexAction() {}

    public function getDouyinAction()
    {
        $url = $this->request->get('url', '');
        if (!$url || !is_url($url)) {
            $this->ajax->response(AJAX::ERROR, _lang('地址有误'));
        }

        $d    = new Douyin();
        $data = $d->getDownUrl($url);

        if ($data['result']) {
            $this->ajax->response(AJAX::SUCCESS, '', $data['url']);
        }

        $this->ajax->response(AJAX::ERROR, _lang('地址有误'));
    }
}
