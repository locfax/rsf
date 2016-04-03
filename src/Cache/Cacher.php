<?php

namespace Rsf\Cache;

use \Rsf\Exception;
use Rsf\Context;

class Cacher {

    use \Rsf\Base\Singleton;

    private $config;
    private $prefix;
    private $cacher;
    public $enable;
    public $type;

    public function __construct() {
        $this->config = getini('cache');
        if ('file' == $this->config['cacher']) {
            $this->cacher = File::getInstance()->init();
            $this->enable = $this->cacher->enable;
            $this->type = 'file';
        } elseif ('memcache' == $this->config['cacher'] && $this->config['memcache']['ready']) {
            $this->cacher = Memcache::getInstance()->init(Context::dsn('memcache'));
            $this->enable = $this->cacher->enable;
            $this->type = 'memcache';
        } elseif ('redis' == $this->config['cacher'] && $this->config['redis']['ready']) {
            $this->cacher = Redis::getInstance()->init(Context::dsn('redis'));
            $this->enable = $this->cacher->enable;
            $this->type = 'redis';
        } elseif ('xcache' == $this->config['cacher'] && $this->config['xcache']['ready']) {
            $this->cacher = Xcache::getInstance()->init();
            $this->enable = $this->cacher->enable;
            $this->type = 'xcache';
        } else {
            throw new Exception\Exception('不存在的缓存器', 0);
        }
        $this->prefix = $this->config['prefix'];
        if (!$this->cacher->enable && 'file' != $this->config['cacher']) {
            $this->defcacher();
        }
    }

    private function defcacher() {
        $this->cacher = File::getInstance()->init();
        $this->enable = $this->cacher->enable;
        $this->type = 'file';
    }

    public function get($key) {
        $ret = null;
        if ($this->enable) {
            $json = $this->cacher->get($this->_key($key));
            if (!$json) {
                return $ret;
            } else {
                $ret = json_decode($json, true);
                return $ret[0];
            }
        }
        return $ret;
    }

    public function set($key, $value, $ttl = 0) {
        $ret = false;
        if ($this->enable) {
            $data = array($value);
            $ret = $this->cacher->set($this->_key($key), output_json($data), $ttl);
        }
        return $ret;
    }

    public function rm($key) {
        $ret = false;
        if ($this->enable) {
            $ret = $this->cacher->rm($this->_key($key));
        }
        return $ret;
    }

    public function clear() {
        $ret = false;
        if ($this->enable) {
            $ret = $this->cacher->clear();
        }
        return $ret;
    }

    private function _key($str) {
        return $this->prefix . $str;
    }

}
