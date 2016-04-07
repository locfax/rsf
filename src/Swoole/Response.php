<?php

namespace Rsf\Swoole;

use \Rsf\Http;

class Response extends Http\Response {

    private $_response = null;

    public function __construct($swoole_response) {
        parent::__construct();
        $this->_response = $swoole_response;
    }

    public function withCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = 0, $httponly = true) {
        $key = sprintf('%s@%s:%s', $name, $domain, $path);
        $this->cookies[$key] = [$name, $value, $expire, $path, $domain, $secure, $httponly];
        return $this;
    }

    public function redirect($url, $status = 303) {
        return $this->withStatus($status)->withHeader('Location', $url);
    }

    public function write($data) {
        $this->getBody()->write($data);
        return $this;
    }

    public function end($data = null) {
        if ($this->end) {
            return $this;
        }
        $this->end = true;
        if ($data !== null) {
            $this->write($data);
        }
        $this->send();
        return $this;
    }

    protected function send() {
        $status = $this->getStatusCode();
        if ($status && $status !== 200) {
            $this->_response->status($status);
        }
        if(!empty($this->headers)) {
            foreach ($this->headers as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $this->_response->header($key, $value);
            }
        }
        if(!empty($this->cookies)) {
            foreach ($this->cookies as list($name, $value, $expire, $path, $domain, $secure, $httponly)) {
                $this->_response->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
            }
        }
        $body = $this->getBody();
        if ($body instanceof Http\IteratorStream) {
            foreach ($body->iterator() as $string) {
                $this->_response->write($string);
            }
            $this->_response->end('');
        } else {
            $this->_response->end((string)$body);
        }
    }
}
