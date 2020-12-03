<?php
declare (strict_types = 1);

namespace External;

class Douyin extends Common
{
    protected $result = ['result' => false, 'url' => ''];
    /**
     * @param  string 链接
     * @return string 下载链接
     */
    public function getDownUrl($url = ''): array
    {
        $target_url = $this->implementApi($url);

        // 匹配出item id
        $res = preg_match('/video\/(\d*)\/\?region=CN/i', $target_url, $item_id);
        if (count($item_id) < 1) {
            return $this->result;
        }
        return $this->removeWatermark($item_id[1]);
    }

    /**
     * @param  item_id 作品id
     * @return string  下载链接
     */
    public function removeWatermark($item_id = 0): array
    {
        if (!$item_id) {
            return $this->result;
        }

        $url  = C('url.douyinNoWatermark') . $item_id;
        $data = $this->curl($url);

        if (!(isset($data['item_list'])
            && isset($data['item_list'][0])
            && $data['item_list'][0]['video']
            && $data['item_list'][0]['video']['play_addr']
            && $data['item_list'][0]['video']['play_addr']['url_list']
        )) {
            return $this->result;
        }

        $url = $data['item_list'][0]['video']['play_addr']['url_list'][0];

        $url = preg_replace('/playwm/', 'play', $url);

        if (is_url($url)) {
            $this->result = [
                'result' => true,
                'url'    => $url,
            ];
        }
        return $this->result;
    }

    /**
     * 调用接口获取信息
     * @param    api    接口地址
     * @param    method uri地址
     * @param    data   参数
     * @return
     */
    private function implementApi($api = '')
    {
        $user_agent = 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Mobile Safari/537.36';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
