<?php

define('DATA', PSROOT . '/data/'); //全局数据路径
define('APP', PSROOT . '/app/');

$_CFG = [];
$_CFG['data']['_cache'] = DATA . 'cache/';
$_CFG['data']['_view'] = DATA . 'tplcache/';
$_CFG['data']['_acl'] = DATA . 'acl/';
$_CFG['data']['lang'] = APP . 'themes/lang/';
$_CFG['data']['tpldir'] = APP . 'themes/' . strtolower(APPKEY) . '/'; //模板路径
$_CFG['data']['tplrefresh'] = 1; //刷新模板 1 自动刷新 其它不更新

$_CFG['cache']['memcache']['ready'] = 0; //是否启动 memcache
$_CFG['cache']['xcache']['ready'] = 0; //是否启动 xcache
$_CFG['cache']['redis']['ready'] = 0; //是否启动 redis
$_CFG['cache']['mysql']['ready'] = 1; //是否启动 mysql
