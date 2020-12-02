<?php
/**
 * 常量定义
 */
define('BASE_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', BASE_PATH . '/app');

// 引导项目所在目录
define('BOOT_PATH', APP_PATH . '/bootstrap');

// 项目配置
define('CONF_PATH', APP_PATH . '/config');

// 数据目录
define('DATA_PATH', BASE_PATH . '/data');

// 函数库目录
define('FUNC_PATH', APP_PATH . '/functions');

// 外部库所在目录
define('VEN_PATH', BASE_PATH . '/vendor');

/**
 * 定义开发环境
 * 如果服务器定义了 APP_ENV 变量，则以 APP_ENV 值作为环境定义
 *
 * @example for nginx config
 *     location ~ \.php$ {
 *         ...
 *         fastcgi_param APP_ENV 'PRODUCTION'; # PRODUCTION|TESTING|DEVELOPMENT
 *     }
 */
if (isset($_SERVER['APP_ENV'])) {
    defined($env = strtoupper($_SERVER['APP_ENV'])) || define($env, true);
    unset($env, $_SERVER['APP_ENV']);
}

// 生产环境
defined('PRODUCTION') || define('PRODUCTION', is_file('/etc/php.env.production'));
// 测试环境
defined('TESTING') || define('TESTING', is_file('/etc/php.env.testing'));
// 开发环境
defined('DEVELOPMENT') || define('DEVELOPMENT', !(PRODUCTION || TESTING));

// 环境常量
if (PRODUCTION) {
    defined('ENV') || define('ENV', 'PRODUCTION');
} elseif (TESTING) {
    defined('ENV') || define('ENV', 'TESTING');
} else {
    defined('ENV') || define('ENV', 'DEVELOPMENT');
}

// 是否 CLI 模式
define('IS_CLI', (PHP_SAPI === 'cli'));
if (IS_CLI) {
    define('IS_AJAX', false);
    define('IS_CURL', false);
    define('HTTP_HOST', null);
    define('HTTP_PROTOCOL', null);
    define('HTTP_BASE', null);
    define('HTTP_URL', null);
} else {

    // 定义是否 AJAX 请求
    define('IS_AJAX',
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        'xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])
    );

    // 定义是否 cURL 请求
    define('IS_CURL', isset($_SERVER['HTTP_USER_AGENT']) &&
        stripos($_SERVER['HTTP_USER_AGENT'], 'curl') !== false);

    // 定义主机地址
    define('HTTP_HOST',
        isset($_SERVER['HTTP_X_FORWARDED_HOST'])
            ? strtolower($_SERVER['HTTP_X_FORWARDED_HOST'])
            : strtolower($_SERVER['HTTP_HOST'])
    );

    // 定义 HTTP 协议
    define('HTTP_PROTOCOL', (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === false) ? 'http' : 'https');

    // 定义是否 SSL
    define('HTTP_SSL', HTTP_PROTOCOL === 'https');

    // 定义当前基础域名
    define('HTTP_BASE',
        ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443')
            ? (HTTP_PROTOCOL . '://' . HTTP_HOST . '/')
            : (HTTP_PROTOCOL . '://' . HTTP_HOST . ':' . $_SERVER['SERVER_PORT'] . '/')
    );

    // 定义当前页面 URL 地址
    define('HTTP_URL', rtrim(HTTP_BASE, '/') . $_SERVER['REQUEST_URI']);
}
