<?php
declare (strict_types = 1);

use Phalcon\DI\Injectable as LoggerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

/**
 * 获取发版时间
 */
function app_time()
{
    if ($time = redis('app_update_time')) {
        return $time;
    }
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
    return filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $url);
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
 * 简写的 $_GET
 *
 * @link   http://docs.phalconphp.com/en/latest/reference/request.html
 * @link   http://docs.phalconphp.com/en/latest/api/Phalcon_Http_Request.html
 *
 * @param  string  $name
 * @param  mixed   $default
 * @param  mixed   $filters
 * @return mixed
 */
function _g($name = null, $default = null, $filters = null)
{
    return service('request')->getQuery($name, $filters, $default);
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
 * 调用 Logger 记录 request 请求
 *
 * @param string                                  $name
 * @param \Psr\Log\LoggerInterface                $logger
 * @param \Psr\Http\Message\RequestInterface|null $request
 * @param integer|null                            $level
 * @param integer|null                            $id
 */
function log_request($name, LoggerInterface $logger, RequestInterface $request = null, $level = LogLevel::INFO, $id = null)
{
    if ($request === null) {
        $request = request();
    }

    $str = sprintf("curl -X %s '%s'", $request->getMethod(), $request->getUri());

    if ($request->getMethod() == 'POST') {
        $content = http_stream_contents($request);
        $json    = json_decode($content, true);
        if ($json) {
            $content = json_encode_improve($json);
        } else {
            parse_str($content, $arr);
            if (!empty($arr)) {
                $content = http_build_query($arr);
            }
        }

        $str = sprintf("%s -d '%s'", $str, str_replace("'", "\'", $content));
    }

    $logger->log($name, $level, "$id req: $str");
}

/**
 * 调用 Logger 记录 response 请求
 *
 * @param string                                   $name
 * @param \Psr\Log\LoggerInterface                 $logger
 * @param \Psr\Http\Message\ResponseInterface|null $request
 * @param integer|null                             $level
 * @param integer|null                             $id
 */
function log_response($name, LoggerInterface $logger, ResponseInterface $response = null, $level = LogLevel::INFO, $id = null)
{
    if ($response === null) {
        $response = response();
    }

    $message = (string) $response->getBody();

    $json = json_decode($message, true);
    if ($json) {
        $message = json_encode_improve($json);
    }

    $logger->log($name, $level, "$id res: $message", process_cost());
}

// 计算消耗的时间和内存，单位分别为秒和兆
function process_cost()
{
    $second = strval(round(microtime(true) - START_TIME, 2));
    $memory = strval(round((memory_get_usage() - START_MEMORY) / 1024 / 1024, 2));
    return compact('second', 'memory');
}

/**
 * 设置 session 值
 *
 * @link  http://docs.phalconphp.com/en/latest/reference/session.html
 * @link  http://docs.phalconphp.com/en/latest/api/Phalcon_Session_AdapterInterface.html
 *
 * @param string $name
 * @param mixed  $value
 */
function session($name, $value = '')
{
    static $cached = [];
    // get
    if ('' === $value) {
        if (isset($cached[$name])) {
            return $cached[$name];
        }

        if (strpos($name, '.') > 0) {
            // 支持按点获取
            $keys    = explode('.', $name);
            $session = service('session')->get(array_shift($keys), null);
            foreach ($keys as $k) {
                if (is_object($session) && isset($session->$k)) {
                    $session = $session->$k;
                    continue;
                }
                if (is_array($session) && isset($session[$k])) {
                    $session = $session[$k];
                    continue;
                }

                return null;
            }

            return $cached[$name] = $session;
        }

        return $cached[$name] = service('session')->get($name, null);
    }

    // delete
    if (null === $value) {
        unset($cached[$name]);

        return service('session')->remove($name);
    }
    // set
    return $cached[$name] = service('session')->set($name, $value);
}

/**
 * 密码比对
 * @param  string    $enterPw 输入的密码
 * @param  string    $salt    生成的加密串
 * @param  string    $passwd  保存的密码
 * @return boolean
 */
function compare_passwd($enterPw = '', $salt = '', $passwd = '')
{
    return md5(md5($enterPw) . $salt) == $passwd;
}

/**
 * 生成密码
 * @param  string  $passwd 需要加密的密码
 * @return array
 */
function gen_passwd($passwd)
{
    $data = [
        'password' => '',
        'salt'     => random_token(3),
    ];
    $data['password'] = md5(md5($passwd) . $data['salt']);
    return $data;
}

/**
 * 获取完整的 url 地址
 *
 * @link   http://docs.phalconphp.com/zh/latest/api/Phalcon_Mvc_Url.html
 *
 * @param  string   $uri
 * @return string
 */
function url($uri = null)
{
    // 网址链接及非正常的 url，纯锚点 (#...) 和 (javascript:)
    if (preg_match('~^(#|javascript:|https?://|telnet://|ftp://|tencent://)~', $uri)) {
        return $uri;
    }

    return service('url')->get(ltrim($uri, '/'));
}

/**
 * 根据 query string 参数生成 url
 *
 *     url_param('item/list', array('page' => 1)) // item/list?page=1
 *     url_param('item/list?page=1', array('limit' => 10)) // item/list?page=1&limit=10
 *
 * @param  string   $uri
 * @param  array    $params
 * @return string
 */
function url_param($uri, array $params = null)
{
    if ($uri === null) {
        $uri = HTTP_URL;
    }

    if (empty($params)) {
        return $uri;
    }

    $parts   = parse_url($uri);
    $queries = [];
    if (isset($parts['query']) && $parts['query']) {
        parse_str($parts['query'], $queries);
    }

    // xss 修正
    $params = array_merge($queries, $params);
    foreach ($params as $key => &$val) {
        $val = htmlspecialchars((string) $val, ENT_QUOTES);
    }

    // 重置 query 组件
    $parts['query'] = rawurldecode(http_build_query($params, '', '&amp;'));

    return http_build_url($uri, $parts);
}

/**
 * 获取 JS 网址
 *
 * @param  string   $jsfile
 * @return string
 */
function url_js($jsfile = null, $time = false)
{
    if ($jsfile) {
        $jsfile = ltrim($jsfile, '/');
        if (empty($jsfile)) {
            $time = false;
        }
    }

    return url_static('js/' . $jsfile, $time);
}

/**
 * 获取静态资源地址
 *
 * @link   http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Url.html
 *
 * @param  string   $uri
 * @param  string   $time
 * @return string
 */
function url_static($uri = null, $time = true)
{
    $params = $time && !preg_match('~(&|\?)t=\d+$~i', $uri) ? ['t' => PRODUCTION ? app_time() : time()] : null;
    return preg_match('~^https?://~i', $uri)
        ? url_param($uri, $params)
        : url_param(service('url')->getStatic(ltrim($uri, '/')), $params);
}

/**
 * 加载 js
 */
function require_js($name)
{
    $baseUrl = url_js();
    $urlArgs = PRODUCTION ? app_time() : time();

    return <<<RJS
<script type="text/javascript" src="{$baseUrl}{$name}?{$urlArgs}"></script>
RJS;
}

// 判断请求是否为ajax的post
function is_ajax_post()
{
    return service('request')->isAjax() && service('request')->isPost();
}

// 判断请求是否为ajax的post
function is_ajax_get()
{
    return service('request')->isAjax() && service('request')->isGet();
}

if (!function_exists('http_stream_contents')) {
    /**
     * 获取 http 请求的内容数据
     *
     * @param  \Psr\Http\Message\MessageInterface|\Psr\Http\Message\StreamInterface $httpEntity
     * @return string|null
     */
    function http_stream_contents($httpEntity)
    {
        if (is_object($httpEntity) && method_exists($httpEntity, 'getBody')) {
            $stream = $httpEntity->getBody();
        } else {
            $stream = $httpEntity;
        }

        if (!$stream instanceof \Psr\Http\Message\StreamInterface) {
            return null;
        }

        $stream->rewind();

        return $stream->getContents();
    }
}
