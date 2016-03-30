<?php

namespace Rsf\Cache;

class Mysql {

    use \Rsf\Base\Singleton;

    public $enable = false;
    private $_link = null;

    public function init() {
        $this->_link = \Rsf\Db::dbo('general');
        $this->enable = true;
        return $this;
    }

    public function get($key) {
        $ret = false;
        $row = $this->_link->findOne('common_cache', "SELECT * FROM %s WHERE cname='{$key}'");
        if ($row) {
            if (0 == $row['expiry']) {
                $ret = $row['data'];
            } elseif ($row['expiry'] >= time()) {
                $ret = $row['data'];
            }
        }
        return $ret;
    }

    public function set($key, $value, $ttl = 0) {
        $expiry = $ttl > 0 ? time() + $ttl : 0;
        $data = array(
            'cname' => $key,
            'dateline' => time(),
            'data' => $value,
            'expiry' => $expiry
        );
        $ret = $this->_link->replace('common_cache', $data);
        return $ret;
    }

    public function rm($key) {
        return $this->_link->remove('common_cache', "cname='{$key}'");
    }

    public function clear() {
        return $this->_link->remove('common_cache', "1");
    }

}
