<?php

namespace Rsf;

class Controller {

    //用户信息
    protected $login_user = null;
    //当前控制器
    protected $request;
    //当前动作
    protected $response;
    //时间戳
    protected $timestamp;


    /**
     * Controller constructor.
     * @param Swoole\Request $request
     * @param Swoole\Response $response
     */
    public function __construct(Swoole\Request $request, Swoole\Response $response) {
        $this->request = $request;
        $this->response = $response;
        $this->init_var();
        $this->init_cache();
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments) {
        $this->response('Action ' . $name . '不存在!', 500);
    }

    /**
     * @param $data
     * @param int $code
     */
    protected function response($data, $code = 200) {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'text/html; charset=' . getini('site/charset'));
        $this->response->withBody(new Http\StringStream($data));
    }

    /*
     * 初始变量
     */

    private function init_var() {
        $this->timestamp = $this->request->getServerParam('REQUEST_TIME') ?: time();
    }

    /*
     * 初始缓存
     */
    private function init_cache() {
        $caches = getini('cache/default');
        loadcache($caches);
    }

    /**
     * @return null
     */
    final function checklogin() {
        if ($this->login_user) {
            return $this->login_user;
        }
        $this->login_user = Context::getUser();
        return $this->login_user;
    }

    /**
     * @param $controllerName
     * @param $actionName
     * @param bool $auth
     * @return bool
     */
    final function checkacl($controllerName, $actionName, $auth = AUTH) {
        return Rbac::check($controllerName, $actionName, $auth);
    }

}
