<?php

return [
    /**
     * @link http://docs.phalconphp.com/en/latest/reference/loader.html
     */
    'loader'    => [
        'dirs'       => [
            'controllersDir' => APP_PATH . '/controllers/',
            'modelsDir'      => APP_PATH . '/models/',
            'pluginsDir'     => APP_PATH . '/plugins/',
            'libraryDir'     => APP_PATH . '/library/',
        ],
        'namespaces' => [],
    ],

    /**
     * @link  http://docs.phalconphp.com/zh/latest/reference/volt.html
     */
    'volt'      => [
        'options' => [
            'path'      => DATA_PATH . '/volt/',
            'extension' => '',
            'separator' => '_',
            'always'    => true,
        ],
    ],

    /**
     * @link http://docs.phalconphp.com/en/latest/reference/url.html
     * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Url.html
     */
    'url'       => [
        'baseUri' => HTTP_BASE,
    ],

    /**
     * @link http://docs.phalconphp.com/en/latest/reference/views.html
     * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
     */
    'views'     => [
        'viewsDir' => APP_PATH . '/views/',
    ],

    /**
     * 自动加载的函数库
     */
    'functions' => [
        'functions' => 1,
        'app'       => 1,
    ],

    /**
     * @link http://docs.phalconphp.com/en/latest/reference/db.html
     * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Db_Adapter_Pdo_Mysql.html
     */
    'database'  => include CONF_PATH . '/' . env() . '/database.php',

    /**
     * @see https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Cache/Backend
     */
    'redis'     => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'lifetime' => 86400, // 1 天
        'prefix'   => 'watermark.',
    ],

    /**
     * Cookies 参数
     */
    'cookies'   => [
        'lifetime' => 604800,           // 默认生存周期 7 天，单位：秒
        'path'     => '/',              // Cookie 存储路径
        'domain'   => '.watermark.com', // Cookie 域名范围
        'secure'   => false,            // 是否启用 https 连接传输
        'httponly' => false,            // 仅允许 http 访问，禁止 javascript 访问
        'encrypt'  => false,            // 是否启用 crypt 加密
    ],

    /**
     * @link http://docs.phalconphp.com/zh/latest/api/Phalcon_Session.html
     * @link http://docs.phalconphp.com/zh/latest/reference/session.html
     * @link https://github.com/phalcon/incubator/blob/1.3.0/Library/Phalcon/Session/Adapter/README.md
     */
    'session'   => [
        'adapter'  => 'redis',
        'lifetime' => 1800,
        'path'     => 'tcp://127.0.0.1:6379?weight=1',
    ],

    /**
     * 多语言设置
     */
    'i18n'      => [
        'key'       => 'lang',             // $_REQUEST 键名 & Cookie 名
        'default'   => 'zh-cn',            // 默认语言
        'directory' => APP_PATH . '/i18n', // 语言包所在目录
        'aliases'   => [                   // 语言别名，因为 \Phalcon\Http\Request::getBestLanguage 有可能获得缩写
            'zh-cn' => ['zh', 'cn'],
            'en-us' => ['en', 'us'],
            'zh-hk' => ['tw', 'zh-tw', 'hk', 'zh-hk'],
        ],
        'supports'  => [
            'en-us' => 'English',
            'zh-cn' => '中文',
            'zh-hk' => '中文繁体',
        ],
        'import'    => [], // 默认加载的语言包
    ],
];
