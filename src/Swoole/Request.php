<?php

namespace Rsf\Swoole;

class Request extends \Rsf\Http\Request {

    //protected $swoole_request;

    /**
     * Request constructor.
     * @param null $swoole_request
     */
    public function __construct($swoole_request) {

        //$this->swoole_request = $swoole_request;

        $server = isset($swoole_request->server) ? array_change_key_case($swoole_request->server, CASE_UPPER) : [];
        $headers = isset($swoole_request->header) ? array_change_key_case($swoole_request->header, CASE_LOWER) : [];

        $get = isset($swoole_request->get) ? $swoole_request->get : [];
        $post = isset($swoole_request->post) ? $swoole_request->post : [];
        $files = isset($swoole_request->files) ? $swoole_request->files : [];

        $cookies = isset($swoole_request->cookie) ? $swoole_request->cookie : [];

        parent::__construct($server, $headers, $get, $post, $files, $cookies);
    }

}
