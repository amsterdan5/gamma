<?php
namespace Base;

class Config extends \Phalcon\DI\Injectable
{

    protected static $_cached = [];

    /**
     * 构造方法
     */
    public function __construct() {}

    public function get($item, $default = null)
    {
        if (isset(self::$_cached[$item])) {
            return self::$_cached[$item];
        }

        if (empty($item)) {
            return $default;
        }

        $keys = explode('.', $item);
        $file = APP_PATH . '/config/' . env() . '/' . array_shift($keys) . '.php';

        if (!is_file($file)) {
            return $default;
        }

        if (is_array($data = include $file)) {
            $data = new \Phalcon\Config($data);
        }

        // 针对数组，支持路径方式获取配置，例如：file.key.subkey
        foreach ($keys as $key) {
            $data = isset($data[$key]) ? $data[$key] : $default;
        }
        self::$_cached[$item] = $data; // 设置缓存

        return $data;
    }
}
