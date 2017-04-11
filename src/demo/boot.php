<?php

define('APPKEY', 'Restful');
define('PSROOT', __DIR__);

require PSROOT . '/vendor/autoload.php';

if (!extension_loaded('swoole')) {
    throw new \Exception('Require php extension "swoole"');
}

function get_app() {

    $app = new \Rsf\Application();

    $app->steup(APP);

    $app->setHandle('checksign', function ($request) {
        //HTTPMETHOD（GET/POST）+ api_uri（API的访问URI）+date（即上面的UNIX时间戳）+length（发送body的数据长度）+password（后台颁发的密码）
        $sign = isset($request->header['sign']) ? $request->header['sign'] : ''; //签名
        $date = isset($request->header['date']) ? $request->header['date'] : ''; //重放攻击
        if (!$sign || !$date) {
            return false;
        }
        if ($date - time() > 3600) { //有效为一个小时
            return false;
        }
        $uri = $request->server['request_uri'];
        $method = $request->server['request_method'];
        $body = file_get_contents('php://input');
        $_body = '';
        if ($body) {
            $body = json_decode($body, true);
            ksort($body);
            foreach ($body as $key => $val) {
                $_body .= "{$key}={$val}";
            }
        }
        $md5sign = md5($uri . $method . $date . $_body . getini('app/loc/secret'));
        if ($md5sign === $sign) {
            return true;
        }
        return false;
    });

    return $app;
}

function app_start($config) {

    $app = get_app();

    $server = new \swoole_http_server($config['server']['host'], $config['server']['port']);

    $server->set($config['setting']);

    $server->on('Request', function ($request, $response) use ($app) {
        //请求过滤
        if ($request->server['request_uri'] == '/favicon.ico') {
            $response->end('');
            return;
        }
        /*
        if (!$app->doHandle('checksign', $request)) {
            return $response->end('sign is error');
        }
        */
        $app->request($request, $response);
    });

    $server->on('start', function () use ($config) {
        file_put_contents($config['server']['pid'], posix_getpid());
    });

    $server->on('shutdown', function () use ($config) {
        if (is_file($config['server']['pid'])) {
            unlink($config['server']['pid']);
        }
    });

    //子进程启动时加载配置
    $server->on('workerstart', function () {
        reload_inc();
    });

    //开始服务
    $server->start();
}

function reload_inc() {
    require PSROOT . '/config/base.inc.php';
    require PSROOT . '/config/' . strtolower(APPKEY) . '.inc.php';
}