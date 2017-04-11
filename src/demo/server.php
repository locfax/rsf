<?php

require './boot.php';

$command = isset($argv[1]) ? $argv[1] : '';

switch ($command) {
    case 'start':
        _start();
        break;
    case 'stop':
        _stop();
        break;
    case 'status':
        _status();
        break;
    case 'reload':
        _reload();
        break;
    default:
        echo "usage: php -q server.php [start|stop|reload|status]\n";
        exit(1);
}

exit(0);

function _start() {
    $pid = _pid();
    if ($pid) {
        echo sprintf("other server run at pid %d\n", $pid);
        exit(1);
    }

    echo "server start\n";
    $config = _config();
    app_start($config);
}

function _stop() {
    $pid = _pid();
    if (!$pid) {
        echo "server not run\n";
        exit(1);
    }

    posix_kill($pid, SIGTERM);
    echo "server stoped\n";
}

function _status() {
    $pid = _pid();
    if ($pid) {
        echo sprintf("server run at pid %d\n", $pid);
    } else {
        echo "server not run\n";
    }
}

function _reload() {
    $pid = _pid();
    if (!$pid) {
        echo "server not run\n";
        exit(1);
    }
    posix_kill($pid, SIGUSR1); //发送usr1重启信号
    echo "server reloaded\n";
}

function _config() {
    static $config = null;
    if (is_null($config)) {
        $config = parse_ini_file(PSROOT . '/app.ini', true);
        if (isset($config['server']['pid'])) {
            $config['server']['pid'] = PSROOT . '/' . $config['server']['pid'];
        }
        if (isset($config['setting']['log_file'])) {
            $config['setting']['log_file'] = PSROOT . '/' . $config['setting']['log_file'];
        }
    }
    return $config;
}


function _pid() {
    $config = _config();
    $pid = $config['server']['pid'];
    $pid = is_file($pid) ? file_get_contents($pid) : 0;
    // 检查进程是否真正存在
    if ($pid && !posix_kill($pid, 0)) {
        $errno = posix_get_last_error();
        if ($errno === 3) {
            $pid = 0;
        }
    }
    return $pid;
}