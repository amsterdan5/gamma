<?php
/**
 * 简化 Phalcon\Di::getDefault()->getShared($service)
 *
 *     service('url')
 *     service('db')
 *     ...
 *
 * @link   http://docs.phalconphp.com/en/latest/api/Phalcon_DI.html
 *
 * @param  string   $service
 * @return object
 */
function service($service)
{
    return Phalcon\DI::getDefault()->getShared($service);
}

/**
 * 加载局部视图, 常用于view中
 *
 * @link   http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 *
 * @param  string   $partialPath
 * @param  array    $params
 * @return string
 */
function partial($partialPath, array $params = null)
{
    return service('view')->partial($partialPath, $params);
}

/**
 * 获取当前环境
 */
function env()
{
    return PRODUCTION ? 'pro' : (TESTING ? 'testing' : 'dev');
}

/**
 * 加载配置文件数据
 *
 *     config('database')
 *     config('database.default.adapter')
 *
 * @param  string  $name
 * @return mixed
 */
function C($name, $default = null)
{
    static $cached = [];

    // 移除多余的分隔符
    $name = trim($name, '.');

    if (isset($cached[$name])) {
        return null === $cached[$name] ? $default : $cached[$name];
    }

    // 获取配置名及路径
    if (strpos($name, '.') === false) {
        $paths    = [];
        $filename = $name;
    } else {
        $paths    = explode('.', $name);
        $filename = array_shift($paths);
    }

    if (isset($cached[$filename])) {
        $data = $cached[$filename];
    } else {
        // 默认优先查找 php 数组类型的配置
        // 查找不到时，根据支持的配置类型进行查找 (注意类型的优先顺序)
        $drivers = [
            'php'  => null,
            'yaml' => '\Phalcon\Config\Adapter\Yaml',
            'json' => '\Phalcon\Config\Adapter\Json',
            'ini'  => '\Phalcon\Config\Adapter\Ini',
        ];

        // 根据路径加载配置文件
        $loadConfig = function ($path) use ($filename, $drivers) {
            foreach ($drivers as $ext => $class) {
                $file = "$path/$filename.$ext";
                if (is_file($file)) {
                    if ($class === null) {
                        return include $file;
                    }

                    return new $class($file);
                }
            }

            return false;
        };

        // 当前配置环境路径
        $path = APP_PATH . '/config/' . env();

        // 尝试加载配置文件
        if (!$data = $loadConfig($path)) {
            $data = $loadConfig(dirname($path));
        }

        if (is_array($data)) {
            $data = new \Phalcon\Config($data);
        }

        // 缓存文件数据
        $cached[$filename] = $data;
    }

    // 支持路径方式获取配置，例如：config('file.key.subkey')
    foreach ($paths as $key) {
        $data = isset($data->{$key}) ? $data->{$key} : null;
    }

    // 缓存数据
    $cached[$name] = $data;

    return null === $cached[$name] ? $default : $cached[$name];
}

/**
 * 不报错，不转义中文的json_encode
 *
 * @param  array    $arr
 * @param  int      $flag
 * @return string
 */
function json_encode_improve($arr, $flag = null)
{
    $opt = JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE;
    if ($flag) {
        $opt |= $flag;
    }
    return json_encode($arr, $opt);
}

/**
 * 生成随机字符串
 *
 * @param  integer  $length 长度，越长越随机
 * @return string
 */
function random_token($length = 8)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
    if (function_exists('mcrypt_create_iv')) {
        return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
    }
    return uniqid();
}

/**
 * 获取客户端IP
 * @return string
 */
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_FORMAX_REAL_IP'])) {
        $ip = $_SERVER['HTTP_FORMAX_REAL_IP'];
    } elseif (method_exists(app('request'), 'ip')) {
        $ip = app('request')->ip();
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // When a HTTP request is proxied, some proxy server will add requester's
        // IP address to $_SERVER['HTTP_X_FORWARDED_FOR'].
        // As a request may go through several proxies,
        // $_SERVER['HTTP_X_FORWARDED_FOR'] can contain several IP addresses separated with comma.
        foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $address) {
            $address = trim($address);

            // Skip RFC 1918 IP's 10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16
            if (!preg_match('/^(?:10|172\.(?:1[6-9]|2\d|3[01])|192\.168)\./', $address)) {
                if (ip2long($address) != false) {
                    $ip = $address;
                    break;
                }
            }
        }
    } else {
        $ip = array_get($_SERVER, 'HTTP_CLIENT_IP', $_SERVER['REMOTE_ADDR']);
    }

    return $ip;
}

/**
 * 隐藏当前系统路径
 *
 *     maskroot('/web/myapp/app/config/db.php') // ~/app/config/db.php
 *
 * @param  string   $path
 * @return string
 */
function maskroot($path)
{
    return str_replace(ROOT_PATH, '~', $path);
}

/**
 * 从数组中获取值，如果未设定时，返回默认值
 *
 * @param  array   $array
 * @param  string  $name
 * @param  mixed   $default
 * @return mixed
 */
function array_get($array, $name, $default = null)
{
    if (is_array($array) && isset($array[$name])) {
        return $array[$name];
    } elseif (is_object($array) && isset($array->$name)) {
        return $array->$name;
    }

    return $default;
}

/**
 * 转换驼峰式字符串为下划线风格
 *
 *     uncamel('lowerCamelCase') === 'lower_camel_case'
 *     uncamel('UpperCamelCase') === 'upper_camel_case'
 *     uncamel('ThisIsAString') === 'this_is_a_string'
 *     uncamel('notcamelcase') === 'notcamelcase'
 *     uncamel('lowerCamelCase', ' | ') === 'lower | camel | case'
 *
 * @param  string    $string
 * @param  string    $separator
 * @return string
 */
function uncamel($string, $separator = '_')
{
    return str_replace('_', $separator, Phalcon\Text::uncamelize($string));
}

/**
 * 转换下划线字符串为驼峰式风格
 *
 *     camel('lower_camel_case') === 'lowerCamelCase'
 *     camel('upper_camel_case', true) === 'UpperCamelCase'
 *
 * @param  string   $string
 * @param  string   $lower
 * @return string
 */
function camel($string, $upper = false, $separator = '_')
{
    $string = str_replace($separator, '_', $string);

    return $upper ? Phalcon\Text::camelize($string) : lcfirst(Phalcon\Text::camelize($string));
}
