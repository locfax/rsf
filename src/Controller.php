<?php

namespace Rsf;

class Controller {

    //用户信息
    protected $login_user = null;
    //当前控制器
    protected $_ctl;
    //当前动作
    protected $_act;
    //时间戳
    protected $timestamp;

    /*
     * 初始执行
     */
    public function __construct($controllerName, $actionName) {
        $this->_ctl = $controllerName;
        $this->_act = $actionName;
        $this->init_var();
        $this->init_cache();
        //$this->init_timezone();
    }

    public function __destruct() {

    }

    public function __call($name, $arguments) {
        //动作不存在
        if (App::isAjax(true)) {
            $retarr = array(
                'errcode' => 1,
                'errmsg' => '你的运气真好! Action ' . $name . '不存在!',
                'data' => ''
            );
            return rep_send($retarr, 'json');
        }
        $args = '你的运气真好!Action:' . $name . "不存在";
        include template('404', 'default');
    }

    /*
     * 初始变量
     */

    private function init_var() {
        $this->timestamp = getgpc('s.REQUEST_TIME') ?: time();
        if (filter_input(INPUT_GET, 'page')) {
            $_GET['page'] = max(1, filter_input(INPUT_GET, 'page'));
        }
    }

    /*
     * 初始缓存
     */

    private function init_cache() {
        $caches = getini('cache/default');
        $caches && loadcache($caches);
    }

    /*
     * 时区
     */
    private function init_timezone() {
        //php > 5.1
        $timeoffset = getini('settings/timezone');
        $timeoffset && date_default_timezone_set('Etc/GMT' . ($timeoffset > 0 ? '-' : '+') . abs($timeoffset));
    }

    final function checklogin() {
        if ($this->login_user) {
            return $this->login_user;
        }
        $this->login_user = Context::getUser();
        return $this->login_user;
    }

    final function checkacl($controllerName, $actionName, $auth = AUTH) {
        return Rbac::check($controllerName, $actionName, $auth);
    }

}
