<?php
declare (strict_types = 1);

use Phalcon\Dispatcher\Exception as DpException;
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
    $config = $this->getConfig();

    $class  = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->db->host,
        'username' => $config->database->db->username,
        'password' => $config->database->db->password,
        'dbname'   => $config->database->db->dbname,
        'charset'  => $config->database->db->charset,
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    return new $class($params);
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

    $params = array_merge(session_get_cookie_params(), $config);
    session_set_cookie_params(
        $params['lifetime'],
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
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
    $em->attach(
        'dispatch:beforeException',
        function ($event, $dispatcher, $exception) {
            switch ($exception->getCode()) {
                case DpException::EXCEPTION_HANDLER_NOT_FOUND:
                case DpException::EXCEPTION_ACTION_NOT_FOUND:
                    $dispatcher->forward([
                        'controller' => 'error',
                        'action'     => 'show404',
                    ]);
                    return false;
            }
        }
    );

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
