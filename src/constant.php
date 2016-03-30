<?php

define('BASE', __DIR__);
define('GDATA', PSROOT . '/gdata/'); //全局数据路径

//通用配置文件
define('SITEHOST', filter_input(INPUT_SERVER, 'HTTP_HOST')); //网站主机
$scriptname = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
define('SITEPATH', substr($scriptname, 0, strrpos($scriptname, '/')) . '/'); //网站路径

define('FILE_READ_MODE', 0666); //所有者 所有组 具有读写
define('FILE_WRITE_MODE', 0777); //所有者 所有组 具有读写 执行
define('DIR_READ_MODE', 0666); //所有者 所有组 具有读写
define('DIR_WRITE_MODE', 0777); //所有者 所有组 具有读写 执行
