<?php

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
    case 'restart':
        _restart();
        break;
    default:
        echo "usage: php -q server.php [start|stop|reload|status|restart]\n";
        exit(1);
}

exit(0);

function _start() {

    echo 'server is start';

}

function _stop() {

    echo 'server is stop';
}

function _status() {

    echo 'server status info';
}

function _reload() {

    echo 'server is reload';
}

function _restart() {
    _stop();
    _start();
}