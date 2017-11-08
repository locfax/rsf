<?php

namespace Rsf\Swoole;

class Request extends \Rsf\Http\Request {

    protected $allow_client_proxy_ip = false;

    protected $swoole_request;

    /**
     * Request constructor.
     * @param null $swoole_request
     */
    public function __construct($swoole_request) {

        $this->swoole_request = $swoole_request;

        $server = isset($swoole_request->server) ? array_change_key_case($swoole_request->server, CASE_UPPER) : [];
        $headers = isset($swoole_request->header) ? array_change_key_case($swoole_request->header, CASE_LOWER) : [];

        $get = isset($swoole_request->get) ? $swoole_request->get : [];
        $post = isset($swoole_request->post) ? $swoole_request->post : [];
        $files = isset($swoole_request->files) ? $swoole_request->files : [];

        $cookies = isset($swoole_request->cookie) ? $swoole_request->cookie : [];

        parent::__construct($server, $headers, $get, $post, $files, $cookies);
    }

    public function getPOST(){
        return $this->swoole_request->rawContent();
    }

    public function getClientIP() {
        if (!$this->allow_client_proxy_ip || !($ip = $this->getServerParam('http_x_forwarded_for'))) {
            return $this->getServerParam('remote_addr');
        }
        if (strpos($ip, ',') === false) {
            return $ip;
        }
        // private ip range, ip2long()
        $private = [
            [0, 50331647],             // 0.0.0.0, 2.255.255.255
            [167772160, 184549375],    // 10.0.0.0, 10.255.255.255
            [2130706432, 2147483647],  // 127.0.0.0, 127.255.255.255
            [2851995648, 2852061183],  // 169.254.0.0, 169.254.255.255
            [2886729728, 2887778303],  // 172.16.0.0, 172.31.255.255
            [3221225984, 3221226239],  // 192.0.2.0, 192.0.2.255
            [3232235520, 3232301055],  // 192.168.0.0, 192.168.255.255
            [4294967040, 4294967295],  // 255.255.255.0 255.255.255.255
        ];
        $ip_set = array_map('trim', explode(',', $ip));
        // 检查是否私有地址，如果不是就直接返回
        foreach ($ip_set as $key => $ip) {
            $long = ip2long($ip);
            if ($long === false) {
                unset($ip_set[$key]);
                continue;
            }
            $is_private = false;
            foreach ($private as $m) {
                list($min, $max) = $m;
                if ($long >= $min && $long <= $max) {
                    $is_private = true;
                    break;
                }
            }
            if (!$is_private) {
                return $ip;
            }
        }
        return array_shift($ip_set) ?: '0.0.0.0';
    }

}
