<?php

/**
 * 获取发版时间
 */
function app_time()
{
    // 后期加入改为版本号
    return time();
}

/**
 * 检查是否效的 url, 只检查 https 和 http 两种
 *
 * @param  string    $url
 * @return boolean
 */
function is_url($url)
{
    return preg_match('/^https?:\/\//i', filter_var($url, FILTER_VALIDATE_URL));
}

/**
 * 语言转换
 *
 *     // file: ~/app/i18n/{$lang}/category.item.php
 *     _lang('category.item.list')
 *
 *     // data: array('welcome' => 'Hello, :name')
 *     _lang('welcome', array(':name' => 'zhouyl')) // Hello, zhouyl
 *
 * @param  string    $string 要转换的字符串，默认传入中文
 * @param  array     $values 需要替换的参数
 * @param  string    $lang   指定的语言类型
 * @return string
 */
function _lang($string, array $values = null, $lang = null)
{
    return service('i18n')->translate($string, $values, $lang);
}

/**
 * 写入redis缓存
 *
 * @param  string  $key
 * @param  mixed   $data
 * @param  integer $lifetime
 * @param  array   $opts
 * @return mixed
 */
function redis($key, $data = '', $lifetime = 86400, $opts = [])
{
    $item = $key . '.cache';
    if ($data === '') {
        // 取
        return service('redis')->get($item);
    }

    if ($data === null) {
        // 删
        return service('redis')->delete($item);
    }

    // 存
    service('redis')->set($item, $data, $lifetime);

    return $data;
}

/**
 * 简化日志写入方法
 *
 *      Phalcon\Logger::SPECIAL
 *      Phalcon\Logger::CUSTOM
 *      Phalcon\Logger::DEBUG
 *      Phalcon\Logger::INFO
 *      Phalcon\Logger::NOTICE
 *      Phalcon\Logger::WARNING
 *      Phalcon\Logger::ERROR
 *      Phalcon\Logger::ALERT
 *      Phalcon\Logger::CRITICAL
 *      Phalcon\Logger::EMERGENCE
 *      Phalcon\Logger::MERGENCY
 * @link   http://docs.phalconphp.com/en/latest/api/Phalcon_Logger.html
 * @link   http://docs.phalconphp.com/en/latest/api/Phalcon_Logger_Adapter_File.html
 *
 * @param string $name    日志名称
 * @param string $message 日志内容
 * @param string $type    日志类型
 */
function logger($name, $message, $type = null)
{
    $message = preg_replace('/password=(.*)&amount=/', 'password=******&amount=', $message);

    return service('logger')->log($name, $message, $type);
}
