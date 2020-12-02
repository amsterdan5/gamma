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
];
