<?php

namespace Rsf\Swoole;

use \Rsf\Http;

class Response extends Http\Response {

    private $swoole_response;

    public function __construct($swoole_response) {
        parent::__construct();
        $this->swoole_response = $swoole_response;
    }

    protected function send() {
        $status = $this->getStatusCode();
        if ($status && $status !== 200) {
            $this->swoole_response->status($status);
        }
        foreach ($this->headers as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $this->swoole_response->header($key, $value);
        }
        foreach ($this->cookies as list($name, $value, $expire, $path, $domain, $secure, $httponly)) {
            $this->swoole_response->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
        $body = $this->getBody();
        if ($body instanceof Http\IteratorStream) {
            foreach ($body->iterator() as $string) {
                $this->swoole_response->write($string);
            }
            $this->swoole_response->end('');
        } else {
            $this->swoole_response->end((string)$body);
        }
    }
}
