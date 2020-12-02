<?php

class Bootweb
{
    public static function run()
    {
        /**
         * Include Functions
         */
        include APP_PATH . '/functions/functions.php';

        /**
         * Get config service for use in inline setup below
         */
        $config = new Phalcon\Config(include CONF_PATH . '/' . env() . '/application.php');

        // 不需要再次加载
        $config->functions->functions = 0;

        // 加载其他函数
        self::loadFunctions($config);

        /**
         * The FactoryDefault Dependency Injector automatically registers
         * the services that provide a full stack framework.
         */
        $di = new Phalcon\Di\FactoryDefault();

        /**
         * Read services
         */
        include BOOT_PATH . '/services.php';

        /**
         * Handle routes
         */
        include BOOT_PATH . '/router.php';

        /**
         * Include Autoloader
         */
        include BOOT_PATH . '/loader.php';

        /**
         * Handle the request
         */
        $application = new \Phalcon\Mvc\Application($di);

        echo $application->handle($_SERVER['REQUEST_URI'])->getContent();
    }

    /**
     * 加载函数
     */
    protected static function loadFunctions($config)
    {
        foreach ($config->functions as $file => $enabled) {
            if ($enabled) {
                require_once FUNC_PATH . '/' . $file . '.php';
            }
        }
    }
}
