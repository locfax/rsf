<?php

define('APPNAME', 'APP'); //应用名称
define('APPDSN', 'portal'); //默认使用的数据库DSN ID
define('ERRD', true); //SQL debug模式
define('AUTH', false); //是否检验权限 false 不检测权限 / general 普通 有设置权限就检测  没设置就忽略 通常用于后台 / strict 严格 必须设置权限
define('ROUTE', true); //是否启动路由功能

$_CFG['cache']['cacher'] = 'file'; //缓存器有：file memcache redis xcache 优先级从前到后
$_CFG['cache']['prefix'] = 'cache_'; //缓存关键字前缀


// 默认存储
$_CFG['file']['site'] = 'loc';

//本地文件存储
$_CFG['file']['loc']['name'] = '本地';
$_CFG['file']['loc']['key'] = 'loc';
$_CFG['file']['loc']['dir'] = realpath('./upload') . '/';
$_CFG['file']['loc']['pfix'] = 'yd';
$_CFG['file']['loc']['ffix'] = '';
$_CFG['file']['loc']['url'] = 'upload/';

$_CFG['auth']['handle'] = 'COOKIE'; //客户端用户数据保存方式 COOKIE, SESSION
$_CFG['auth']['prefix'] = 'cmw_'; //用户数据前缀
$_CFG['auth']['domain'] = ''; // cookie session domain
$_CFG['auth']['path'] = '/'; // cookie session 作用路径
$_CFG['auth']['key'] = 'webpcsdscq7sEOd35N3ad'; // cookie加密关键字

$_CFG['app']['loc']['id'] = '111';
$_CFG['app']['loc']['secret'] = '222';

\Rsf\Context::mergeVars('cfg', $_CFG); //加入初始化

$_CFG = null;
