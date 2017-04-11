<?php

namespace Rsf;

class Context {

    use Traits\Context;

    private static $_dsn = [];

    /**
     * @param $dsnid
     * @return mixed
     * @throws Exception\Exception
     */
    public static function dsn($dsnid) {
        if (!isset(self::$_dsn[APPKEY])) {
            $dsns = self::config(APPKEY, 'dsn');
            foreach ($dsns as $key => $dsn) {
                $dsns[$key]['dsnkey'] = md5($dsn['driver'] . '_' . $dsn['host'] . '_' . $dsn['port'] . '_' . $dsn['login'] . '_' . $dsn['database']); //连接池key
            }
            self::$_dsn[APPKEY] = $dsns;
            if (!isset(self::$_dsn[APPKEY][$dsnid])) {
                return null;
            }
            $dsns = null;
        }
        //默认为正确的配置
        return self::$_dsn[APPKEY][$dsnid];
    }

    /**
     * @param $name
     * @param $type
     * @return bool|mixed
     */
    public static function config($name, $type = 'inc') {
        $file = PSROOT . '/config/' . strtolower($name) . '.' . $type . '.php';
        if (!is_file($file)) {
            return [];
        }
        return include $file;
    }

    /**
     * @param $group
     * @param null $vars
     * @return mixed
     */
    public static function mergeVars($group, $vars = null) {
        static $_CDATA = ['cfg' => null];
        if (is_null($vars)) {
            return $_CDATA[$group];
        } else {
            if (is_null($_CDATA[$group])) {
                $_CDATA[$group] = $vars;
            } else {
                $_CDATA[$group] = array_merge($_CDATA[$group], $vars);
            }
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
