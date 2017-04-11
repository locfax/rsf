<?php

namespace Rsf;

class Controller {

    //用户信息
    protected $login_user = null;
    //当前控制器
    protected $request = null;
    //当前动作
    protected $response = null;

    /**
     * Controller constructor.
     * @param Swoole\Request $request
     * @param Swoole\Response $response
     */
    public function __construct(Swoole\Request $request, Swoole\Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws Exception\Exception
     */
    public function __call($name, $arguments) {
        if ($this->request->isAjax()) {
            $res = array(
                'errcode' => 1,
                'errmsg' => 'Action ' . $name . '不存在!'
            );
            $this->repjson($res, 500);
        } else {
            $this->rephtml('Action ' . $name . '不存在!', 500);
        }
    }

    /**
     * @param String $data
     * @param int $code
     */
    protected function rephtml($data = '', $code = 200) {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'text/html; charset=' . getini('site/charset'));
        $this->response->write($data);
    }

    /**
     * @param array $data
     * @param int $code
     */
    protected function repjson($data = [], $code = 200) {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'application/json; charset=' . getini('site/charset'));
        $data = $data ? output_json($data) : '';
        $this->response->write($data);
    }

    protected function render() {
        $data = ob_get_contents();
        ob_clean();
        return $data;
    }

    /**
     * @return null
     */
    public function checklogin() {
        if ($this->login_user) {
            return $this->login_user;
        }
        $this->login_user = User::getUser();
        return $this->login_user;
    }

    /**
     * @param $controllerName
     * @param $actionName
     * @param bool $auth
     * @return bool
     */
    public function checkacl($controllerName, $actionName, $auth = AUTH) {
        return Rbac::check($controllerName, $actionName, $auth);
    }

}
