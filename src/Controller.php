<?php

namespace Rsf;

class Controller
{
    //swoole请求
    protected $request;

    //swoole回应
    protected $response;

    // 当前请求类型
    protected $_method;

    protected $_action;

    // REST允许的请求类型列表
    private $allow_method = ['get', 'post', 'put', 'delete'];

    public function init(Swoole\Request $request, Swoole\Response $response, $actionName)
    {
        $this->request = $request;
        $this->response = $response;

        // 请求方式检测
        $method = strtolower($request->getMethod());
        if (!in_array($method, $this->allow_method)) {
            $method = 'get';
        }
        $this->_method = $method;
        $this->_action = $actionName;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if ($this->request->isAjax()) {
            $res = [
                'errcode' => 1,
                'errmsg' => 'Action ' . $name . '不存在!'
            ];
            $this->resjson($res);
        } else {
            $this->response('Action ' . $name . '不存在!');
        }
    }

    protected function request()
    {

    }

    /**
     * @param String $data
     * @param int $code
     */
    protected function response($data = '', $code = 200)
    {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'text/html; charset=' . getini('site/charset'));
        $this->response->end($data);
    }

    /**
     * @param array $data
     * @param int $code
     */
    protected function resjson($data = [], $code = 200)
    {
        if ($code !== 200) {
            $this->response->withStatus($code, Http\Http::getStatus($code));
        }
        $this->response->withHeader('Content-Type', 'application/json; charset=' . getini('site/charset'));
        $data = $data ? Util::output_json($data) : '';
        $this->response->end($data);
    }

    protected function render_start()
    {
        ob_start();
    }

    protected function render_end()
    {
        $data = ob_get_contents();
        ob_clean();
        return $data;
    }

}
