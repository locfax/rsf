<?php

namespace Rsf;

class RestFul extends Controller
{
    // 当前请求类型
    private $_method = null;
    // REST允许的请求类型列表
    private $allow_method = ['get', 'post', 'put', 'delete'];

    public function __construct()
    {
        // 请求方式检测
        $method = strtolower($this->request->method);
        if (!in_array($method, $this->allow_method)) {
            $method = 'get';
        }
        $this->_method = $method;
        $this->request();
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $res = [
            'errcode' => 1,
            'errmsg' => 'Action ' . $name . '不存在!'
        ];
        $this->resjson($res);
    }

    protected function request()
    {
        if ('get' == $this->_method) {
            call_user_func(array($this, '_' . $this->_method . '_get'));
        } elseif ('post' == $this->_method) {
            call_user_func(array($this, '_' . $this->_method . '_post'));
        } elseif ('put' == $this->_method) {
            call_user_func(array($this, '_' . $this->_method . '_put'));
        } elseif ('delete' == $this->_method) {
            call_user_func(array($this, '_' . $this->_method . '_delete'));
        }
    }
}
