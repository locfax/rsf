<?php

namespace Rsf\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Request implements ServerRequestInterface {

    use MessageTrait;

    protected $server;
    protected $get;
    protected $post;
    protected $files;
    protected $cookies;
    protected $method;
    protected $uri;

    public function __construct($server, $headers, $get, $post, $files, $cookies) {
        $this->server = $server;
        $this->headers = $headers;

        $this->get = $get;
        $this->post = $post;
        $this->files = $files;
        $this->cookies = $cookies;

        $this->body = new ResourceStream(fopen('php://input', 'r'));
    }

    public function __clone() {
        $this->method = null;
        $this->uri = null;
    }

    public function getRequestTarget() {
        return isset($this->server['REQUEST_URI']) ? $this->server['REQUEST_URI'] : '/';
    }

    public function withRequestTarget($requestTarget) {
        $result = clone $this;
        $result->server['REQUEST_URI'] = $requestTarget;
        return $result;
    }

    public function getMethod() {
        if ($this->method !== null) {
            return $this->method;
        }
        $method = isset($this->server['REQUEST_METHOD']) ? strtoupper($this->server['REQUEST_METHOD']) : 'GET';
        if ($method !== 'POST') {
            return $this->method = $method;
        }
        $override = $this->getHeader('x-http-method-override') ?: $this->post('_method');
        if ($override) {
            if (is_array($override)) {
                $override = array_shift($override);
            }
            $method = $override;
        }
        return $this->method = strtoupper($method);
    }

    public function withMethod($method) {
        $result = clone $this;
        $result->method = strtoupper($method);

        return $result;
    }

    public function getUri() {
        if ($this->uri) {
            return $this->uri;
        }
        $scheme = $this->getServerParam('HTTPS') ? 'https' : 'http';
        $user = $this->getServerParam('PHP_AUTH_USER');
        $password = $this->getServerParam('PHP_AUTH_PW');
        $host = $this->getServerParam('SERVER_NAME') ?: $this->getServerParam('SERVER_ADDR') ?: '127.0.0.1';
        $port = $this->getServerParam('SERVER_PORT');
        return $this->uri = (new Uri($this->getRequestTarget()))
            ->withScheme($scheme)
            ->withUserInfo($user, $password)
            ->withHost($host)
            ->withPort($port);
    }

    public function withUri(UriInterface $uri, $preserveHost = false) {
        throw new \Exception('Request::withUri() not implemented');
    }

    public function getServerParams() {
        return $this->server;
    }

    public function getCookieParams() {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies) {
        $result = clone $this;
        $result->cookies = $cookies;
        return $result;
    }

    public function getQueryParams() {
        return $this->get;
    }

    public function withQueryParams(array $query) {
        $result = clone $this;
        $result->get = $query;
        return $result;
    }

    public function getUploadedFiles() {
        return $this->files;
    }

    public function withUploadedFiles(array $uploadFiles) {
        throw new \Exception('Request::withUploadedFiles() not implemented');
    }

    public function getParsedBody() {
        $content_type = $this->getHeaderLine('content-type');
        $method = $this->getServerParam('REQUEST_METHOD');
        if ($method === 'POST' && ($content_type === 'application/x-www-form-urlencoded' || $content_type === 'multipart/form-data')) {
            return $this->post;
        }
        $body = (string)$this->body;
        if ($body === '') {
            return null;
        }
        if ($content_type === 'application/json') {
            return json_decode($body, true);
        }
        return $body;
    }

    public function withParsedBody($data) {
        throw new \Exception('Request::withParsedBody() not implemented');
    }

    public function getServerParam($name) {
        $name = strtoupper($name);
        return isset($this->server[$name]) ? $this->server[$name] : false;
    }

    public function getCookieParam($name) {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : false;
    }

    public function get($key = null) {
        if ($key === null) {
            return $this->get;
        }
        return isset($this->get[$key]) ? $this->get[$key] : null;
    }

    public function post($key = null) {
        if ($key === null) {
            return $this->post;
        }
        return isset($this->post[$key]) ? $this->post[$key] : null;
    }

    public function hasGet($key) {
        return array_key_exists($key, $this->get);
    }

    public function hasPost($key) {
        return array_key_exists($key, $this->post);
    }

    public function isGet() {
        return $this->getMethod() === 'GET' || $this->getMethod() === 'HEAD';
    }

    public function isPost() {
        return $this->getMethod() === 'POST';
    }

    public function isPut() {
        return $this->getMethod() === 'PUT';
    }

    public function isDelete() {
        return $this->getMethod() === 'DELETE';
    }

    public function isAjax() {
        $val = $this->getHeader('x-requested-with');
        return $val && (strtolower($val[0]) === 'xmlhttprequest');
    }

    /**
     * 构造http请求对象，供测试使用.
     *
     * @example
     * $request = Request::factory([
     *     'uri' => '/',
     *     'method' => 'post',
     *     'cookies' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'headers' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'get' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'post' => [
     *         $key => $value,
     *         ...
     *     ],
     * ]);
     */
    public static function factory($options = []) {
        $options = array_merge([
            'uri' => '/',
            'method' => 'GET',
            'cookies' => [],
            'headers' => [],
            'get' => [],
            'post' => [],
            'ip' => '',
        ], $options);

        $server = [];
        $server['REQUEST_METHOD'] = strtoupper($options['method']);
        $server['REQUEST_URI'] = $options['uri'];

        if ($options['ip']) {
            $server['REMOTE_ADDR'] = $options['ip'];
        }

        if ($query = parse_url($options['uri'], PHP_URL_QUERY)) {
            parse_str($query, $get);
            $options['get'] = array_merge($get, $options['get']);
        }

        $cookies = $options['cookies'];
        $get = $options['get'];
        $post = $options['post'];

        if ($server['REQUEST_METHOD'] === 'GET') {
            $post = [];
        }

        return new self($server, $options['headers'], $get, $post, [], $cookies);
    }
}
