<?php
declare (strict_types = 1);

use EasyWeChat\Factory as WX;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Url as UrlResolver;

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return new Phalcon\Config(include CONF_PATH . '/' . env() . '/application.php');
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->url->baseUri);

    return $url;
});

/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->views->viewsDir);

    $view->registerEngines([
        '.phtml' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions($config->volt->options->toArray());

            return $volt;
        },
    ]);

    return $view;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig()->database;

    $class  = 'Phalcon\Db\Adapter\Pdo\\' . $config->db->adapter;
    $params = $config->db->toArray();

    if ($config->db->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    $connection = new $class($params);

    // Create an EventsManager
    $eventsManager = new Phalcon\Events\Manager();

    // Listen all the database events
    $eventsManager->attach('db', $this->get('dbListener'));

    // Assign the events manager to the connection
    $connection->setEventsManager($eventsManager);

    return $connection;
});

/**
 * 监听db
 */
$di->set('dbListener', function () {
    return new \Base\DbListener();
});

/**
 * Register the Redis service
 */
$di->set('redis', function () {
    $config = $this->getConfig()->redis->toArray();

    $adapterFatcory = new Phalcon\Cache\AdapterFactory(new Phalcon\Storage\SerializerFactory());
    $redis          = $adapterFatcory->newInstance('redis', $config);

    return new Phalcon\Cache($redis);
});

/**
 * Register the Cookies service
 */
$di->set('cookies', function () {
    $config  = $this->getConfig()->cookies->toArray();
    $cookies = new Base\Cookies($config);

    if (!isset($_SESSION)) {
        $params = array_merge(session_get_cookie_params(), $config);
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    return $cookies;
});

/**
 * Start the session the first time some component request the session service
 *
 * @link http://docs.phalconphp.com/en/latest/reference/session.html
 * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Session.html
 * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Session_Adapter_Files.html
 */
$di->setShared('session', function () {
    $config = $this->getConfig();

    // 使用指定的 session 存储方式
    if (isset($config->session)) {
        $sessionAdapter = 'Phalcon\Session\Adapter\\' . ucfirst($config->session->adapter);
        $fatcory        = new Phalcon\Storage\AdapterFactory(new Phalcon\Storage\SerializerFactory());

        $adpater = new $sessionAdapter($fatcory, $config->session->toArray());
    } else {
        // 使用 File 存储
        $adpater = new Phalcon\Session\Adapter\Stream([
            'savePath' => sys_get_temp_dir(),
        ]);
    }

    $session = new Phalcon\Session\Manager();
    $session->setAdapter($adpater);
    $session->start();
    return $session;
});

/**
 * 多语言设置
 */
$di->set('i18n', function () use ($di) {
    $config  = $this->getConfig()->i18n;
    $request = $di->get('request');
    $cookies = $di->get('cookies');

    if ($request->has($config->key)) {
        $default = $request->get($config->key);
    } elseif ($cookies->has($config->key)) {
        $default = $cookies($config->key);
    } else {
        $default = $request->getBestLanguage();
    }

    // 初始化
    $i18n = new Base\I18n();
    $i18n->addDirectory($config->directory)
        ->addAliases($config->aliases->toArray())
        ->import($config->import->toArray());

    // 根据别名取语言类型
    $default = $i18n->getLangByAlias($default);
    $i18n->setDefault(isset($config->supports[$default]) ? $default : $config->default);

    $cookies->set($config->key, $i18n->getDefault(86400 * 30));
    return $i18n;
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
// $di->setShared('modelsMetadata', function () {
//     return new MetaDataAdapter();
// });

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    $escaper = new Escaper();
    $flash   = new Flash($escaper);
    $flash->setImplicitFlush(false);
    $flash->setCssClasses([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning',
    ]);

    return $flash;
});

/**
 * We register the events manager
 *
 * @link http://docs.phalconphp.com/en/latest/reference/dispatching.html
 * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Dispatcher.html
 */
$di->set('dispatcher', function () use ($di) {
    $em = $di->getShared('eventsManager');
    $em->attach('dispatch:beforeException', new ExceptionsPlugin($di));

    $dispatcher = new MvcDispatcher();
    $dispatcher->setEventsManager($em);
    return $dispatcher;
});

/**
 * 注册 AJAX
 */
$di->set('ajax', function () {
    return new AJAX();
});

/**
 * 日志服务
 */
$di->set('logger', function () {
    return new Base\Logger();
});

/**
 * 微信公众号
 */
$di->set('wx', function () {
    $config   = C('wx.config');
    $cli      = IS_CLI ? '_cli' : '';
    $log_file = LOGS_PATH . date('/Ymd') . $cli . '/wechat.log';

    $data = [
        'app_id'        => $config->appId,
        'secret'        => $config->appSecret,
        'token'         => $config->token,
        'aes_key'       => $config->encodeKey,
        'response_type' => $config->responseType,
        'log'           => [
            'level' => 'debug',
            'file'  => $log_file,
        ],
    ];
    $app = WX::officailAccount($data);
    return $app->server->serve();
});

/**
 * 小程序
 */
$di->set('wxProgram', function () {
    $config   = C('wx.config');
    $cli      = IS_CLI ? '_cli' : '';
    $log_file = LOGS_PATH . date('/Ymd') . $cli . '/wechat_program.log';

    $data = [
        'app_id'        => $config->appId,
        'secret'        => $config->appSecret,
        'token'         => $config->token,
        'aes_key'       => $config->encodeKey,
        'response_type' => $config->responseType,
        'log'           => [
            'level' => 'debug',
            'file'  => $log_file,
        ],
    ];
    $app = WX::miniProgram($config);
    return $app->server->serve();
});
