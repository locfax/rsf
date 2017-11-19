<?php

namespace Rsf;

use Rsf\Exception;

class App {

    const _dCTL = 'c';
    const _dACT = 'a';
    const _controllerPrefix = '\\';
    const _actionPrefix = 'act_';

    private $handlers = [];

    /**
     * @param $root
     */
    public function steup($root) {
        set_error_handler(function ($errno, $error, $file = null, $line = null) {
            throw new \ErrorException($error, $errno);
        });
        $this->rootnamespace('\\', $root);
    }

    /**
     * @param $key
     * @param $handle
     */
    public function setHandle($key, $handle) {
        $this->handlers[$key] = $handle;
    }

    /**
     * @param $key
     * @param $param
     * @return bool|mixed
     */
    public function doHandle($key, $param) {
        if (!isset($this->handlers[$key])) {
            return true;
        }
        return call_user_func($this->handlers[$key], $param);
    }

    /**
     * @param $request
     * @param $response
     */
    public function request($request, $response) {
        $request = new Swoole\Request($request);
        $response = new Swoole\Response($response);
        $this->dispatching($request, $response);
    }

    /**
     * @param $request
     * @param $response
     */
    public function dispatching(Swoole\Request $request, Swoole\Response $response) {
        if (defined('ROUTE') && ROUTE) {
            $uri = $request->getRequestTarget();
            $router = Route::parse_routes($uri);
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

        $this->execute($controllerName, $actionName, $request, $response);
    }


    /**
     * @param $controllerName
     * @param $actionName
     * @param $request
     * @param $response
     */
    private function execute($controllerName, $actionName, Swoole\Request $request, Swoole\Response $response) {
        $controllerName = ucfirst($controllerName);
        $actionMethod = self::_actionPrefix . $actionName;

        $controllerClass = self::_controllerPrefix . APPKEY . '\\' . $controllerName;
        try {
            $controller = new $controllerClass($request, $response);
            call_user_func([$controller, $actionMethod]);
        } catch (\ErrorException $exception) { //cache异常
            $this->exception($exception, $response);
        } catch (\Exception $exception) { //普通异常
            $this->exception($exception, $response);
        } catch (\Throwable $exception) { //PHP7
            $this->exception($exception, $response);
        }
    }

    /**
     * @param mixed $exception
     * @param $response
     */
    private function exception($exception, Swoole\Response $response) {
        $data = $this->exception2str($exception);
        $response->withStatus(500, Http\Http::getStatus(500));
        $response->end($data);
        DB::close(); //关闭数据库
    }

    /**
     * @param mixed $exception
     * @return string
     */
    private function exception2str($exception) {
        $output = '<h3>' . $exception->getMessage() . '</h3>';
        $output .= '<p>' . nl2br($exception->getTraceAsString()) . '</p>';
        if ($previous = $exception->getPrevious()) {
            $output = $this->exception2str($previous) . $output;
        }
        return $output;
    }

    /**
     * @param $group
     * @param null $vars
     * @return mixed
     */
    public static function mergeVars($group, $vars = null) {
        static $_CDATA = array(APPKEY => array('dsn' => null, 'cfg' => null, 'data' => null));
        $appkey = APPKEY;
        if (is_null($vars)) {
            return $_CDATA[$appkey][$group];
        }
        if (is_null($_CDATA[$appkey][$group])) {
            $_CDATA[$appkey][$group] = $vars;
        } else {
            $_CDATA[$appkey][$group] = array_merge($_CDATA[$appkey][$group], $vars);
        }
        return true;
    }

    /**
     * @param $namespace
     * @param $path
     */
    public function rootnamespace($namespace, $path) {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/');
        $loader = function ($classname) use ($namespace, $path) {
            if ($namespace && stripos($classname, $namespace) !== 0) {
                return;
            }
            $file = trim(substr($classname, strlen($namespace)), '\\');
            $file = $path . '/' . str_replace('\\', '/', $file) . '.php';
            if (!is_file($file)) {
                throw new \Exception($file . '不存在');
            }
            require $file;
        };
        spl_autoload_register($loader);
    }

}