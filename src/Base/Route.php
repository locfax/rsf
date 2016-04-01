<?php

namespace Rsf\Base;

class Route {

    static $routes = null;

    public static function parse_routes($uri) {
        if (strpos($uri, 'index.php') !== false) {
            $uri = substr($uri, strpos($uri, 'index.php') + 10);
        }
        if (!$uri) {
            return false;
        }
        if (!self::$routes) {
            self::$routes = \Rsf\Context::config('route');
        }
        foreach (self::$routes as $key => $val) {
            $key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);
            if (preg_match('#' . $key . '#', $uri, $matches)) {
                if (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE) {
                    $val = preg_replace('#' . $key . '#', $val, $uri);
                }
                $req = explode('/', $val);
                return $req;
            }
        }
        return false;
    }
}