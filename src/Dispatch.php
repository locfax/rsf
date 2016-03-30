<?php

namespace Rsf;

class Dispatch {

    const _dCTL = 'ctl';
    const _dACT = 'act';
    const _controllerPrefix = '\\Apps\\';
    const _actionPrefix = 'act_';

    /**
     * @return bool
     * @throws Exception
     */
    public static function dispatching() {
        //TODO 一个入口只能调用一次
        //TOTO router 要增强该功能
        if (defined('ROUTE') && ROUTE) {
            $uri = $_SERVER['PHP_SELF'];
            if (strpos($uri, 'index.php') >= 0) {
                $uri = substr($uri, strpos($uri, 'index.php') + 10);
            }
            Route::parse_routes($uri);
        }
        //execute method
        $_controllerName = getgpc('g.' . self::_dCTL, getini('site/defaultController'), 'strtolower');
        $_actionName = getgpc('g.' . self::_dACT, getini('site/defaultAction'), 'strtolower');
        $controllerName = preg_replace('/[^a-z0-9_]+/i', '', $_controllerName);
        $actionName = preg_replace('/[^a-z0-9_]+/i', '', $_actionName);
        if (defined('AUTH') && AUTH) {
            $ret = Rbac::check($controllerName, $actionName, AUTH);
            if (!$ret) {
                $args = ' : ' . $controllerName . ' - ' . $actionName;
                return self::errACT($args);
            }
        }
        return self::executeAction($controllerName, $actionName);
    }


    /**
     * @param $controllerName
     * @param $actionName
     * @return bool
     * @throws Exception\Exception
     */
    public static function executeAction($controllerName, $actionName) {
        $controllerName = ucfirst($controllerName);
        $actionMethod = self::_actionPrefix . $actionName;
        do {
            $controllerClass = self::_controllerPrefix . APPKEY . '\\' . $controllerName;
            $controller = new $controllerClass($controllerName, $actionMethod);
            $controller->{$actionMethod}();
            $controller = null;
            return true;
        } while (false);
        //控制器加载失败
        if (App::isAjax(true)) {
            $retarr = array(
                'errcode' => 1,
                'errmsg' => "The controller '" . $controllerName . '\' is not exists!',
                'data' => ''
            );
            return rep_send($retarr, 'json');
        }
        throw new Exception\Exception("The controller '" . $controllerName . '\' is not exists!', 0);
    }

    /**
     * @param $args
     * @return bool
     */
    private static function errACT($args) {
        if (App::isAjax(true)) {
            $retarr = array(
                'errcode' => 1,
                'errmsg' => '你没有权限访问该页面!',
                'data' => ''
            );
            return rep_send($retarr, 'json');
        }
        $args = '你没有权限访问该页面!' . $args;
        include template('403', 'default');
    }

}
