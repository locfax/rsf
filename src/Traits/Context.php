<?php

namespace Rsf\Traits;

use \Rsf\Exception;

/**
 * @example
 *
 * class Foobar {
 *     use \Rsf\Traits\Context;
 *
 *     public function __construct() {
 *         $this->setContextHandler(\Rsf\Context::factory('redis', $config));
 *     }
 * }
 *
 * $foobar = new Foobar;
 *
 * $foobar->setContext($key, $value);
 * $value = $foobar->getContext($key);
 */
trait Context {

    protected $context_handler;

    public function setContext($key, $val) {
        return $this->getContextHandler(true)->set($key, $val);
    }

    public function getContext($key = null) {
        return $this->getContextHandler(true)->get($key);
    }

    public function removeContext($key) {
        return $this->getContextHandler(true)->rm($key);
    }

    public function clearContext() {
        return $this->getContextHandler(true)->clear();
    }

    public function setContextHandler($handler) {
        $this->context_handler = $handler;
    }

    public function getContextHandler($throw_exception = false) {
        if (!$this->context_handler && $throw_exception) {
            throw new Exception\Exception('Please set context handler before use');
        }
        return $this->context_handler ?: false;
    }
}
