<?php

namespace Rsf\Cache;

use \Rsf\Exception;

class Memcache {

    use \Rsf\Base\Singleton;

    public $enable = false;
    private $_link = null;

    public function init($config) {
        if (!extension_loaded('memcache')) {
            throw new Exception\CacheException('memcache 扩展没安装?', 0);
        }
        try {
            $this->_link = new \Memcache();
            if ($config['pconnect']) {
                $connect = $this->_link->pconnect($config['host'], $config['port'], $config['timeout']);
            } else {
                $connect = $this->_link->connect($config['host'], $config['port'], $config['timeout']);
            }
            if ($connect) {
                $this->enable = true;
            }
        } catch (\MemcachedException $e) {
            throw new Exception\CacheException($e->getMessage(), $e->getCode());
        }
        return $this;
    }

    public function get($key) {
        try {
            return $this->_link->get($key);
        } catch (\MemcachedException $e) {
            return false;
        }
    }

    public function set($key, $value, $ttl = 0) {
        try {
            if ($ttl > 0) {
                return $this->_link->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
            }
            return $this->_link->set($key, $value);
        } catch (\MemcachedException $e) {
            return false;
        }
    }

    public function rm($key) {
        try {
            return $this->_link->delete($key);
        } catch (\MemcachedException $e) {
            return false;
        }
    }

    public function clear() {
        return $this->_link->flush();
    }

}
