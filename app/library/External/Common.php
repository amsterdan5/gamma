<?php
declare (strict_types = 1);

namespace External;

/**
 * 外链公共类
 */
class Common
{
    public function curl($url = '', $data = [])
    {
        $params = http_build_query($data);

        if (!empty($params)) {
            $url .= '?' . $params;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $data = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($data, true);

        if (!isset($result->data)) {
            return $result;
        }

        return $result->data;
    }
}
