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
     * @throws \Exception
     */
    public function __call($name, $arguments) {
        if ($this->request->isAjax()) {
            $res = array(
                'errcode' => 1,
                'errmsg' => 'Action ' . $name . '不存在!'
            );
            $this->resjson($res);
        } else {
            $this->response('Action ' . $name . '不存在!');
        }
    }

    /**
     * @param String $data
     * @param int $code
     */
    protected function response($data = '', $code = 200) {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'text/html; charset=' . getini('site/charset'));
        $this->response->write($data);
        $this->response->end('');
    }

    /**
     * @param array $data
     * @param int $code
     */
    protected function resjson($data = array(), $code = 200) {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'application/json; charset=' . getini('site/charset'));
        $data = $data ? Util::output_json($data) : '';
        $this->response->write($data);
        $this->response->end('');
    }

    protected function render_start() {
        ob_start();
    }

    protected function render_end() {
        $data = ob_get_contents();
        ob_clean();
        return $data;
    }

}
