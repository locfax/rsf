<?php

$mysql_host = '192.168.1.85';
$mysql_user = 'locphp';
$mysql_pass = 'locphp';
$mysql_db = 'loc_votodo';
$mysql_port = '3306';

$mongo_host = '192.168.1.85';
$mongo_user = '';
$mongo_pass = '';
$mongo_db = 'loc_votodo';
$mongo_port = '27017';

$redis_host = '192.168.1.85';
$redis_user = '';
$redis_pass = '';
$redis_db = 'loc_redis';
$redis_port = '6379';

return [
    'general' => [//用于保存系统配置 缓存等数据
        'driver' => 'pdox',
        'dsn' => "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_db}",
        'login' => $mysql_user,
        'secret' => $mysql_pass,
        'rundev' => true
    ],
    'portal' => [//默认主业务数据库
        'driver' => 'pdox',
        'dsn' => "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_db}",
        'login' => $mysql_user,
        'secret' => $mysql_pass,
        'rundev' => true
    ],
    'redis.cache' => [
        'host' => $redis_host,
        'port' => $redis_port,
        'login' => $redis_user,
        'password' => $redis_pass,
        'database' => $redis_db,
        'pconnect' => 0,
        'timeout' => 2.5,
        'rundev' => true
    ],
    'memcache.cache' => [
        'host' => '127.0.0.1',
        'port' => '11211',
        'login' => '',
        'password' => '',
        'database' => '',
        'pconnect' => 0
    ]
];
