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
