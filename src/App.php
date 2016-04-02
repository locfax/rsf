<?php

namespace Rsf;

class App {

    const _dCTL = 'ctl';
    const _dACT = 'act';
    const _controllerPrefix = '\\Apps\\';
    const _actionPrefix = 'act_';

    /**
     * @param $request
     * @param $response
     */
    public static function start($request, $response) {
        App::rootNamespace('\\', PSROOT);
        $request = new Swoole\Request($request);
        $response = new Swoole\Response($response);

        self::dispatching($request, $response);
        $response->end();

        //if (function_exists('fastcgi_finish_request')) {
        //   fastcgi_finish_request();
        //}
    }

    /**
     * @return bool
     */
    public static function dispatching(Swoole\Request $request, Swoole\Response $response) {
        if (defined('ROUTE') && ROUTE) {
            $uri = $request->getRequestTarget();
            $router = Base\Route::parse_routes($uri);
            if ($router) {
                $_controllerName = array_shift($router);
                $_actionName = array_shift($router);
            } else {
                $_controllerName = $request->get(self::_dCTL) ?: getini('site/defaultController');
                $_actionName = $request->get(self::_dACT) ?: getini('site/defaultAction');
            }
        } else {
            $_controllerName = $request->get(self::_dCTL) ?: getini('site/defaultController');
            $_actionName = $request->get(self::_dACT) ?: getini('site/defaultAction');
        }
        $controllerName = preg_replace('/[^a-z0-9_]+/i', '', $_controllerName);
        $actionName = preg_replace('/[^a-z0-9_]+/i', '', $_actionName);
        if (defined('AUTH') && AUTH) {
            $allow = Rbac::check($controllerName, $actionName, AUTH);
            if (!$allow) {
                $args = ' : ' . $controllerName . ' - ' . $actionName;
                return self::errRole($args, $response);
            }
        }
        self::_execute($controllerName, $actionName, $request, $response);
    }


    /**
     * @param $controllerName
     * @param $actionName
     * @return bool
     * @throws Exception\Exception
     */
    private static function _execute($controllerName, $actionName, Swoole\Request $request, Swoole\Response $response) {
        $controllerName = ucfirst($controllerName);
        $actionMethod = self::_actionPrefix . $actionName;
        do {
            $controllerClass = self::_controllerPrefix . APPKEY . '\\' . $controllerName;
            $controller = new $controllerClass($request, $response);
            if (!$controller instanceof $controllerClass) {
                break;
            }
            $controller->{$actionMethod}();
            return true;
        } while (false);
        self::errCtl($controllerName, $response);
    }

    private static function errCtl($controllerName, Swoole\Response $response) {
        //控制器加载失败
        $retarr = array(
            'errcode' => 1,
            'errmsg' => '控制器 \'' . $controllerName . '\' 不存在!',
            'data' => ''
        );
        $response->withStatus(200)->withHeader('Content-type', 'application/json; charset=UTF-8');
        $body = new Http\StringStream(output_json($retarr));
        $response->withBody($body);
    }

    /**
     * @param $args
     * @return bool
     */
    private static function errRole($args, Swoole\Response $response) {
        $retarr = array(
            'errcode' => 2,
            'errmsg' => '你没有权限访问该页面!' . $args,
            'data' => ''
        );
        $response->withStatus(200)->withHeader('Content-type', 'application/json; charset=UTF-8');
        $body = new Http\StringStream(output_json($retarr));
        $response->withBody($body);
    }

    /**
     * @param $namespace
     * @param $path
     */
    public static function rootNamespace($namespace, $path) {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/');
        $loader = function ($classname) use ($namespace, $path) {
            if ($namespace && stripos($classname, $namespace) !== 0) {
                return false;
            }
            $file = trim(substr($classname, strlen($namespace)), '\\');
            $file = $path . '/' . str_replace('\\', '/', $file) . '.php';
            if (!is_file($file)) {
                throw new Exception\Exception($file, 0);
            }
            require $file;
        };
        spl_autoload_register($loader);
    }

    /**
     * @param $group
     * @param null $vars
     * @return mixed
     */
    public static function mergeVars($group, $vars = null) {
        static $_CDATA = array('dsn' => null, 'cfg' => null); //内部变量缓冲
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
        Db::dbo('general')->create('weixin_log', $post);
    }

}