<?php
declare (strict_types = 1);

/**
 * PHP版本检测
 */
version_compare(PHP_VERSION, '7.2.0', '>=') || die('PHP version must be at least 7.2');

/**
 * 检测框架是否安装
 */
extension_loaded('phalcon') || die('Phalcon framework extension is not installed');

/**
 * Phalcon版本检测
 * 1.3.0: Added Phalcon\Image\Adapter, Phalcon\Image\Adapter\Gd, Phalcon\Image\Adapter\Imagick
 */
version_compare(Phalcon\Version::get(), '4.0.4', '>=') || die('Phalcon version must be at least 4.0.4');

/**
 * 检测 PDO_MYSQL
 */
extension_loaded('pdo_mysql') || die('PDO_MYSQL extension is not installed');

/**
 * 建议打开 short_open_tag
 */
ini_get('short_open_tag') || die('Please modify <php.ini> and "short_open_tag" is set to "on"');

/**
 * 默认时区定义
 */
date_default_timezone_set('Asia/Shanghai');

/**
 * 设置默认区域
 */
setlocale(LC_ALL, 'zh_CN.utf-8');

/**
 * 所有的常量配置
 */
require dirname(__DIR__) . '/app/bootstrap/defined.php';

/**
 * 设置错误报告模式
 */
error_reporting(E_ALL | E_STRICT);

/**
 * 异常捕获
 */
set_error_handler(function ($code, $error, $file, $line) {
    throw new ErrorException($error, $code, 0, $file, $line);

    return true;
});

// 崩溃错误
register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    logger('errors', $error['message'] . ' (in ' . $error['file'] . ' on line ' . $error['line'] . ')', Phalcon\Logger::ERROR);
});

/**
 * 打开/关闭错误显示
 */
ini_set('display_errors', PRODUCTION ? '0' : '1');

/**
 * 打开log_errors
 */
ini_set('log_errors', '1');

/**
 * 避免 cli 或 curl 模式下 xdebug 输出 html 调试信息
 */
ini_set('html_errors', (!IS_CLI && !IS_CURL) ? '1' : '0');

try {

    require BOOT_PATH . '/bootweb.php';
    Bootweb::run();
} catch (\Phalcon\Exception $e) {
    logger('errors', $e->getMessage(), Phalcon\Logger::ERROR);
    echo $e->getMessage();
} catch (PDOException $e) {
    logger('db_errors', $e->getMessage(), Phalcon\Logger::ERROR);
    echo $e->getMessage();
}
