<?php

namespace Rsf;

use Rsf\Exception;

class App {

    const _dCTL = 'ctl';
    const _dACT = 'act';
    const _controllerPrefix = '\\Apps\\';
    const _actionPrefix = 'act_';

    public function steup($root) {
        set_error_handler(function ($errno, $error, $file = null, $line = null) {
            //让error变的可以扑捉
            if (error_reporting() & $errno) {
                throw new \ErrorException($error, $errno, $errno, $file, $line);
            }
            return true;
        });
        $this->rootnamespace('\\', $root);
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
    }

    /**
     * @param Swoole\Request $request
     * @param Swoole\Response $response
     * @return bool
     */
    public function dispatching(Swoole\Request $request, Swoole\Response $response) {
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
                return $this->exception(' 你没有权限访问 ' . $controllerName . ' - ' . $actionName, $response);
            }
        }
        $this->execute($controllerName, $actionName, $request, $response);
        return true;
    }


    /**
     * @param $controllerName
     * @param $actionName
     * @return bool
     * @throws Exception\Exception
     */
    private function execute($controllerName, $actionName, Swoole\Request $request, Swoole\Response $response) {
        $controllerName = ucfirst($controllerName);
        $actionMethod = self::_actionPrefix . $actionName;
        do {
            $controllerClass = self::_controllerPrefix . APPKEY . '\\' . $controllerName;
            try {
                $controller = new $controllerClass($request, $response);
                if (!$controller instanceof $controllerClass) {
                    break;
                }
                $data = $controller->{$actionMethod}();
                if ($data) { //不一定有返回值
                    if ($data instanceof \Psr\Http\Message\StreamInterface) {
                        $response->withBody($data);
                    } elseif ($data !== null && !($data instanceof Http\Response)) {
                        $response->write($data);
                    }
                }
            } catch (Exception\Exception $exception) { //普通异常
                $this->exception($exception, $response);
            } catch (Exception\DbException $exception) { //db异常
                $this->exception($exception, $response);
            } catch (Exception\CacheException $exception) { //cache异常
                $this->exception($exception, $response);
            } catch (\Throwable $exception) { //PHP7
                //$this->exception($exception, $response);
            } finally {
                //free singleton and so on...
                Db::close();
            }
            return true;
        } while (false);
        $this->exception('控制器 \'' . $controllerName . '\' 不存在!', $response);
        return false;
    }

    /**
     * @param $exception
     * @param Swoole\Response $response
     * @return bool
     */
    private function exception($exception, Swoole\Response $response) {
        $data = $this->strexception($exception);
        $response->withStatus(500);
        $response->withHeader('Content-type', 'text/html; charset=UTF-8');
        $response->withBody(new Http\StringStream($data));
    }

    private function strexception($exception) {
        $output = '<h3>' . $exception->getMessage() . '</h3>';
        $output .= '<p>' . nl2br($exception->getTraceAsString()) . '</p>';
        if ($previous = $exception->getPrevious()) {
            $output = $this->strexception($previous) . $output;
        }
        return $output;
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
                throw new Exception\Exception($file);
            }
            require $file;
            return true;
        };
        spl_autoload_register($loader);
    }

}