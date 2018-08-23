<?php

namespace Rsf;

class RestFul extends Controller
{
    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $res = [
            'errcode' => 1,
            'errmsg' => 'Action ' . $name . '不存在2!'
        ];
        $this->resjson($res);
    }

    protected function request()
    {
        if ('get' == $this->_method) {
            call_user_func(array($this, '_' . $this->_action . '_get'));
        } elseif ('post' == $this->_method) {
            call_user_func(array($this, '_' . $this->_action . '_post'));
        } elseif ('put' == $this->_method) {
            call_user_func(array($this, '_' . $this->_action . '_put'));
        } elseif ('delete' == $this->_method) {
            call_user_func(array($this, '_' . $this->_action . '_delete'));
        }
    }

}
