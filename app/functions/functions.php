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

    return service('logger')->log($name, $type, $message);
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
    } elseif (method_exists(service('request'), 'ip')) {
        $ip = service('request')->ip();
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
    return str_replace(BASE_PATH, '~', $path);
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

// 把一个对象结构递归变成一数组结构
function o2a($d)
{
    if (is_object($d)) {
        if (method_exists($d, 'getArrayCopy')) {
            $d = $d->getArrayCopy();
        } elseif (method_exists($d, 'getArrayIterator')) {
            $d = $d->getArrayIterator()->getArrayCopy();
        } elseif (method_exists($d, 'toArray')) {
            $d = $d->toArray();
        } else
        // Gets the properties of the given object
        // with get_object_vars function
        {
            $d = get_object_vars($d);
        }
    }

    /*
     * Return array converted to object
     * Using __FUNCTION__ (Magic constant)
     * for recursive call
     */
    if (is_array($d)) {
        return array_map(__FUNCTION__, $d);
    }

    // Return array
    return $d;
}

if (!function_exists('http_build_url')) {
    define('HTTP_URL_REPLACE', 1);          // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2);        // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4);       // Join query strings
    define('HTTP_URL_STRIP_USER', 8);       // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16);      // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32);      // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64);      // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128);     // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256);    // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512); // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024);     // Strip anything but scheme and host

    /**
     * Build an URL
     * The parts of the second URL will be merged into the first according to the flags argument.
     *
     * @param mixed   (Part(s) of) an URL in form of a string or associative array like parse_url() returns
     * @param mixed   Same     as the first argument
     * @param integer A        bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
     * @param array   If       set, it will be filled with the parts of the composed url like parse_url() would return
     */
    function http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, &$new_url = false)
    {
        $keys = ['user', 'pass', 'port', 'path', 'query', 'fragment'];

        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        }
        // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
        elseif ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }

        // Parse the original URL
        $parse_url = parse_url($url);

        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $parse_url['scheme'] = $parts['scheme'];
        }

        if (isset($parts['host'])) {
            $parse_url['host'] = $parts['host'];
        }

        // (If applicable) Replace the original URL with it's new parts
        if ($flags & HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $parse_url[$key] = $parts[$key];
                }
            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                if (isset($parse_url['path'])) {
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
                } else {
                    $parse_url['path'] = $parts['path'];
                }
            }

            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                if (isset($parse_url['query'])) {
                    $parse_url['query'] .= '&' . $parts['query'];
                } else {
                    $parse_url['query'] = $parts['query'];
                }
            }
        }

        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key) {
            if ($flags & (int) constant('HTTP_URL_STRIP_' . strtoupper($key))) {
                unset($parse_url[$key]);
            }
        }

        $new_url = $parse_url;

        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '') .
            ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '') .
            ((isset($parse_url['host'])) ? $parse_url['host'] : '') .
            ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '') .
            ((isset($parse_url['path'])) ? $parse_url['path'] : '') .
            ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '') .
            ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
        ;
    }
}

// 获取当前链接
function get_current_url()
{
    $current_url = 'http://';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $current_url = 'https://';
    }
    if ($_SERVER['SERVER_PORT'] != '80') {
        $current_url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
    } else {
        $current_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    return $current_url;
}

// 获取当前客户的ip
function get_current_ip()
{
    //header Formax　Real　IP
    if (isset($_SERVER['HTTP_FORMAX_REAL_IP'])) {
        $ip = $_SERVER['HTTP_FORMAX_REAL_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = service('request')->getClientAddress();
    }
    //多个代理的处理
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip);
        $ip = $ip[0];
    }

    return $ip;
}

/**
 * xss过滤函数
 *
 * @param  $string
 * @return string
 */
function remove_xss($string)
{
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

    $param1 = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'];

    $param2 = ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'];

    $param = array_merge($param1, $param2);

    for ($i = 0; $i < sizeof($param); $i++) {
        $pattern = '/';
        for ($j = 0; $j < strlen($param[$i]); $j++) {
            if ($j > 0) {
                $pattern .= '(';
                $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                $pattern .= '|(&#0([9][10][13]);?)?';
                $pattern .= ')?';
            }
            $pattern .= $param[$i][$j];
        }
        $pattern .= '/i';
        $string = preg_replace($pattern, '', $string);
    }
    return $string;
}
