<?php

namespace Rsf;

class Route {

    static $routes = null;

    public static function parse_routes($uri) {
        if (!$uri) {
            return;
        }
        if(!self::$routes){
            self::$routes = Context::config('route');
        }
        foreach (self::$routes as $key => $val) {
            $key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);
            if (preg_match('#' . $key . '#', $uri, $matches)) {
                if (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE) {
                    $val = preg_replace('#' . $key . '#', $val, $uri);
                }
                $req = explode('/', $val);
                self::set_request($req);
                return true;
            }
        }
        return false;
    }

    private static function set_request($req) {
        $_GET['ctl'] = array_shift($req);
        $_GET['act'] = array_shift($req);
        $parmnum = count($req);
        if (!$parmnum) {
            return;
        }
        for ($i = 0; $i < $parmnum; $i++) {
            $_GET[$req[$i]] = $req[$i + 1];
            $i++;
        }
    }
}