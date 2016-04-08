<?php

namespace Rsf\Cache;

use \Rsf\Exception;

class Xcache {

    use \Rsf\Base\Singleton;

    public $enable = false;

    public function init() {
        if (!extension_loaded('memcache')) {
            throw new Exception\Exception('memcache 扩展没安装?');
        }
        $this->enable = true;
        return $this;
    }

    public function close() {

    }

    public function get($key) {
        return xcache_get($key);
    }

    public function set($key, $value, $ttl = 0) {
        if ($ttl > 0) {
            return xcache_set($key, $value, $ttl);
        }
        return xcache_set($key, $value);
    }

    public function rm($key) {
        return xcache_unset($key);
    }

    public function clear() {
        xcache_clear_cache(1, -1);
    }

}
