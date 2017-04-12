<?php

namespace Rsf;

class Context {

    use Traits\Context;

    private static $_dsns = [];
    private static $_configs = [];

    /**
     * @param $dsnid
     * @return mixed
     */
    public static function dsn($dsnid) {
        if (!isset(self::$_dsns[APPKEY])) {
            $dsns = self::mergeVars('dsn');
            foreach ($dsns as $key => $dsn) {
                $dsns[$key]['dsnkey'] = md5(APPKEY . '_' . $key . '_' . $dsn['driver'] . '_' . $dsn['dsn']); //连接池key
            }
            self::$_dsns[APPKEY] = $dsns;
            if (!isset(self::$_dsns[APPKEY][$dsnid])) {
                self::$_dsns[APPKEY][$dsnid] = [];
            }
            $dsns = null;
        }
        //如果没配置$dsnid 会报错
        return self::$_dsns[APPKEY][$dsnid];
    }

    /**
     * @param $name
     * @param $type
     * @return bool|mixed
     */
    public static function config($name, $type = 'inc') {
        $key = APPKEY . '.' . $name . '.' . $type;
        if (isset(self::$_configs[$key])) {
            return self::$_configs[$key];
        }
        $file = PSROOT . '/config/' . strtolower($name) . '.' . $type . '.php';
        if (!is_file($file)) {
            self::$_configs[$key] = [];
            return [];
        }
        self::$_configs[$key] = include $file;
        return self::$_configs[$key];
    }

    /**
     * @param $group
     * @param null $vars
     * @return mixed
     */
    public static function mergeVars($group, $vars = null) {
        static $_CDATA = [APPKEY => ['cfg' => null]];
        if (is_null($vars)) {
            return $_CDATA[APPKEY][$group];
        }
        if (is_null($_CDATA[APPKEY][$group])) {
            $_CDATA[APPKEY][$group] = $vars;
        } else {
            $_CDATA[APPKEY][$group] = array_merge($_CDATA[APPKEY][$group], $vars);
        }
        return true;
    }

    /**
     * @param $cmd
     * @param string $key
     * @param string $val
     * @param int $ttl
     * @return bool
     */
    public static function cache($cmd, $key = '', $val = '', $ttl = 0) {
        if (in_array($cmd, ['set', 'get', 'rm', 'clear', 'close'])) {
            $cacher = \Rsf\Cacher::getInstance();
            switch ($cmd) {
                case 'get':
                    return $cacher->get($key);
                case 'set':
                    return $cacher->set($key, $val, $ttl);
                case 'rm':
                    return $cacher->rm($key);
                case 'clear':
                    return $cacher->clear();
                case 'close':
                    return $cacher->close();
            }
        }
        return false;
    }

    /**
     * @param $data
     * @param string $code
     */
    public static function runlog($data, $code = 'debug') {
        $logfile = DATA . 'log/run.log';
        $log = new \Monolog\Logger('run');
        $log->pushHandler(new \Monolog\Handler\StreamHandler($logfile, \Monolog\Logger::WARNING));
        if ($code == 'info') {
            $log->addInfo($data);
        } elseif ($code == 'warn') {
            $log->addWarning($data);
        } elseif ($code == 'error') {
            $log->addError($data);
        } else {
            $log->addDebug($data);
        }
    }
}
