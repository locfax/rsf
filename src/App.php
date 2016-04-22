<?php

namespace Rsf;

use Rsf\Exception;

class App {

    const _dCTL = 'ctl';
    const _dACT = 'act';
    const _controllerPrefix = '\\';
    const _actionPrefix = 'act_';

    private $handlers = [];

    /**
     * @param $root
     */
    public function steup($root) {
        set_error_handler(function ($errno, $error, $file = null, $line = null) {
            if (error_reporting() & $errno) {
                throw new \ErrorException($error, $errno, $errno, $file, $line);
            }
            return true;
        });
        $this->rootnamespace('\\', $root);
    }

    private function finish() {
        try {
            Db::close();
        } catch (\ErrorException $e) {

        }
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
        $response->end();
        $this->finish();
    }

    /**
     * @param $request
     * @param $response
     * @return bool
     */
    public function dispatching($request, $response) {
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
        if (defined('AUTH') && AUTH) {
            $allow = Rbac::check($controllerName, $actionName, AUTH);
            if (!$allow) {
                $this->response(' 你没有权限访问 ' . $controllerName . ' - ' . $actionName, 500, $response);
                return false;
            }
        }
        return $this->execute($controllerName, $actionName, $request, $response);
    }


    /**
     * @param $controllerName
     * @param $actionName
     * @param $request
     * @param $response
     * @return bool
     */
    private function execute($controllerName, $actionName, $request, $response) {
        $controllerName = ucfirst($controllerName);
        $actionMethod = self::_actionPrefix . $actionName;

        $controllerClass = self::_controllerPrefix . APPKEY . '\\' . $controllerName;
        try {
            $controller = new $controllerClass($request, $response);
            call_user_func([$controller, $actionMethod]);
        } catch (Exception\Exception $exception) { //普通异常
            $this->exception($exception, $response);
        } catch (Exception\DbException $exception) { //db异常
            $this->exception($exception, $response);
        } catch (Exception\CacheException $exception) { //cache异常
            $this->exception($exception, $response);
        } catch (\ErrorException $exception) {
            $this->exception($exception, $response);
        } catch (\Throwable $exception) { //PHP7
            $this->exception($exception, $response);
        }
        return true;
    }

    /**
     * @param $exception
     * @param $response
     * @return bool
     */
    private function exception($exception, $response) {
        $data = $this->exception2str($exception);
        $this->response($data, 500, $response);
    }

    private function exception2str($exception) {
        $output = '<h3>' . $exception->getMessage() . '</h3>';
        $output .= '<p>' . nl2br($exception->getTraceAsString()) . '</p>';
        if ($previous = $exception->getPrevious()) {
            $output = $this->strexception($previous) . $output;
        }
        return $output;
    }

    private function response($data, $code = 500, $response) {
        $response->withStatus($code);
        $response->withHeader('Content-type', 'text/html; charset=UTF-8');
        $response->write($data);
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
                return false;
            }
            $file = trim(substr($classname, strlen($namespace)), '\\');
            $file = $path . '/' . str_replace('\\', '/', $file) . '.php';
            if (!is_file($file)) {
                throw new Exception\Exception($file . '不存在');
            }
            require $file;
            return true;
        };
        spl_autoload_register($loader);
    }

}