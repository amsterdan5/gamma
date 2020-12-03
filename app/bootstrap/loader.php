<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader
    ->registerDirs($config->loader->dirs->toArray())
    ->registerNamespaces($config->loader->namespaces->toArray())
    ->register();

/**
 * Register the autoloader of composer
 */
$vendorLoader = VEN_PATH . '/autoload.php';
if (is_file($vendorLoader)) {
    require $vendorLoader;
}
