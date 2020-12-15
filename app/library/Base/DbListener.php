<?php

namespace Base;

use Phalcon\Db\Profiler;

/**
 * 数据库事件监听器
 *
 * @link http://docs.phalconphp.com/en/latest/reference/events.html
 * @link http://docs.phalconphp.com/en/latest/api/Phalcon_Db_Profiler.html
 */
class DbListener
{

    protected $_profiler;

    /**
     * Creates the profiler and starts the logging
     */
    public function __construct()
    {
        $this->_profiler = new Profiler();
    }

    /**
     * This is executed if the event triggered is 'beforeQuery'
     */
    public function beforeQuery($event, $connection)
    {
        $this->_profiler->startProfile($connection->getSQLStatement());
    }

    /**
     * This is executed if the event triggered is 'afterQuery'
     */
    public function afterQuery($event, $connection)
    {
        $this->_profiler->stopProfile();

        $profile = $this->_profiler->getLastProfile();
        $secs    = round($profile->getTotalElapsedSeconds(), 6);

        // write database log
        if ((PRODUCTION && $secs >= 0.5) || !PRODUCTION) {
            logger('database', "[$secs] " . $profile->getSQLStatement());
        }
    }

    public function getProfiler()
    {
        return $this->_profiler;
    }
}
