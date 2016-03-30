<?php

namespace Rsf\Cache;

class Redis {

    use \Rsf\Base\Singleton;

    public $enable = false;
    private $_link = null;

    public function init($config) {
        if (!class_exists('\Redis', false)) {
            throw new \Rsf\Exception\Exception('Redis 扩展没安装?');
        }
        try {
            $this->_link = new \Redis();
            if ($config['pconnect']) {
                $ret = $this->_link->pconnect($config['host'], $config['port'], $config['timeout'], $config['database']);
            } else {
                $ret = $this->_link->connect($config['host'], $config['port'], $config['timeout']);
            }
            if ($ret && $config['password']) {
                $ret = $this->_link->auth($config['login'] . "-" . $config['password'] . "-" . $config['database']);
            }
            if ($ret) {
                $this->_link->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
                $this->enable = true;
            }
        } catch (\RedisException $e) {

        }
        return $this;
    }

    public function get($key) {
        try {
            return $this->_link->get($key);
        } catch (\RedisException $e) {
            return false;
        }
    }

    public function set($key, $value, $ttl = 0) {
        try {
            $ret = $this->_link->set($key, $value);
            if ($ttl > 0) {
                $this->_link->expire($key, $ttl);
            }
            return $ret;
        } catch (\RedisException $e) {
            return false;
        }
    }

    public function rm($key) {
        try {
            return $this->_link->delete($key);
        } catch (\RedisException $e) {
            return false;
        }
    }

    public function clear() {
        try {
            return $this->_link->flushDB();
        } catch (\RedisException $e) {
            return false;
        }
    }

}
