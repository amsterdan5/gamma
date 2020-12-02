<?php
declare (strict_types = 1);

use Phalcon\Dispatcher\Exception as DpException;
use Phalcon\Escaper;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Session\Adapter\Stream as SessionAdapter;
use Phalcon\Session\Manager as SessionManager;
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
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

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
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionManager();
    $files   = new SessionAdapter([
        'savePath' => sys_get_temp_dir(),
    ]);
    $session->setAdapter($files);
    $session->start();

    return $session;
});

/**
 * redirect to 404
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
}, true);
