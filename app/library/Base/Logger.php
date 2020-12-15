<?php

namespace Base;

use Phalcon\Logger as PaLogger;
use Phalcon\Logger\Adapter\Stream;

class Logger extends \Phalcon\DI\Injectable
{
    private static $_formatter = null;
    private static $_adapters  = [];
    private static $_types     = [
        'debug'     => 1,
        'info'      => 1,
        'notice'    => 1,
        'warning'   => 1,
        'error'     => 1,
        'critical'  => 1,
        'alert'     => 1,
        'emergency' => 1,
    ];

    /**
     * 构造方法
     */
    public function __construct()
    {
        if (self::$_formatter !== null) {
            return null;
        }

        self::$_formatter = new \Phalcon\Logger\Formatter\Line();
        self::$_formatter->setDateFormat('Y-m-d H:i:s');
    }

    private function _adapter($name)
    {
        $name = preg_replace('/[^\da-z\-_]/i', '', $name);

        if (isset(self::$_adapters[$name])) {
            return self::$_adapters[$name];
        }

        // 由于　web　用户使用nobody, cli可能使用root用户，
        // 有可能造成cli生产的日志文件，web下面不可写，
        // 故这里区分下，web和cli使用不同的日志
        $cli = IS_CLI ? '_cli' : '';

        $logfile = LOGS_PATH . date('/Ymd') . $cli . '/' . $name . $cli . '.log';
        is_dir($dir = dirname($logfile)) || mkdir($dir, 0777, true);

        $adapter = new Stream($logfile);
        $adapter->setFormatter(self::$_formatter);

        self::$_adapters[$name] = new PaLogger('messages', ['main' => $adapter]);

        return self::$_adapters[$name];
    }

    /**
     * 日志写入方法
     *
     *   Phalcon\Logger::SPECIAL
     *   Phalcon\Logger::CUSTOM
     *   Phalcon\Logger::DEBUG
     *   Phalcon\Logger::INFO
     *   Phalcon\Logger::NOTICE
     *   Phalcon\Logger::WARNING
     *   Phalcon\Logger::ERROR
     *   Phalcon\Logger::ALERT
     *   Phalcon\Logger::CRITICAL
     *   Phalcon\Logger::EMERGENCE
     *   Phalcon\Logger::MERGENCY
     * @link  http://docs.phalconphp.com/en/latest/api/Phalcon_Logger.html
     * @link  http://docs.phalconphp.com/en/latest/api/Phalcon_Logger_Adapter_File.html
     *
     * @param string $name 日志名称
     * @param string $type 日志类型
     * @param string $msg  日志内容
     */
    public function log($name, $type, $msg)
    {
        $type = $type === null ? \Phalcon\Logger::INFO : $type;

        return $this->_adapter($name)->log($type, $msg);
    }

    public function __call($fun, $arguments)
    {
        if (!isset(self::$_types[$fun])) {
            return false;
        }

        if (count($arguments) < 2) {
            return false;
        }

        $name = $arguments[0];
        $msg  = $arguments[1];

        return $this->_adapter($name)->{$fun}($msg);
    }
}
