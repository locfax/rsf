<?php

namespace Rsf;

class Context {

    private static $dsn = [];

    /**
     * @param $dsnid
     * @return mixed
     * @throws Exception\Exception
     */
    public static function dsn($dsnid) {
        if (!isset(self::$dsn[APPKEY])) {
            $dsns = self::config(APPKEY, 'dsn');
            foreach ($dsns as $dsnid => $dsn) {
                $dsns[$dsnid]['dsnkey'] = md5($dsn['driver'] . '_' . $dsn['host'] . '_' . $dsn['port'] . '_' . $dsn['login'] . '_' . $dsn['database']); //连接池key
            }
            self::$dsn[APPKEY] = $dsns;
            if (!isset(self::$dsn[APPKEY][$dsnid])) {
                throw new Exception\Exception('无配置!' . APPKEY . $dsnid);
            }
            $dsns = null;
        }
        //默认为正确的配置
        return self::$dsn[APPKEY][$dsnid];
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
     * @param $code
     * @param $data
     */
    public static function log($code, $data) {
        $post = [
            'dateline' => time(),
            'logcode' => $code,
            'logmsg' => var_export($data, true)
        ];
        Db::dbo('general')->create('weixin_log', $post);
    }

}
