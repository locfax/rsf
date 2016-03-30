<?php

namespace Rsf;

class App {


    public static function rootNamespace($namespace, $path, $classname = null) {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/\\');
        $loader = function ($classname, $return_filename = false) use ($namespace, $path) {
            if (class_exists($classname, false) || interface_exists($classname, false)) {
                return true;
            }
            $classname = trim($classname, '\\');
            if ($namespace && stripos($classname, $namespace) !== 0) {
                return false;
            } else {
                $filename = trim(substr($classname, strlen($namespace)), '\\');
            }
            $filename = $path . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $filename) . '.php';
            if ($return_filename) {
                return $filename;
            } else {
                if (!file_exists($filename)) {
                    return false;
                }
                require $filename;
                return class_exists($classname, false) || interface_exists($classname, false);
            }
        };
        if ($classname === null) {
            spl_autoload_register($loader);
        } else {
            return $loader($classname, true);
        }
    }

    public static function run($root) {
        \Rsf\APP::rootNamespace('\\', $root);
        Dispatch::dispatching();
    }

    /**
     * @param $group
     * @param null $vars
     * @return mixed
     */
    public static function mergeVars($group, $vars = null) {
        static $_CDATA = array('dsn' => null, 'cfg' => null, 'data' => array()); //内部变量缓冲
        if (is_null($vars)) {
            return $_CDATA[$group];
        } else {
            if (is_null($_CDATA[$group])) {
                $_CDATA[$group] = $vars;
            } else {
                $_CDATA[$group] = array_merge($_CDATA[$group], $vars);
            }
        }
    }

    /**
     * @param $code
     * @param $data
     */
    public static function log($code, $data) {
        $post = array(
            'dateline' => time(),
            'logcode' => $code,
            'logmsg' => var_export($data, true)
        );
        DB::get('general')->create('weixin_log', $post);
    }

    /**
     * @param bool $retbool
     * @return bool
     */
    public static function isPost($retbool = true) {
        if ('POST' == getgpc('s.REQUEST_METHOD')) {
            return $retbool;
        }
        return !$retbool;
    }

    /**
     * @param bool $retbool
     * @return bool
     */
    public static function isAjax($retbool = true) {
        if ('XMLHttpRequest' == getgpc('s.HTTP_X_REQUESTED_WITH')) {
            return $retbool;
        }
        return !$retbool;
    }
}