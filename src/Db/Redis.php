<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Redis {

    //dsn information
    private $_dsn = null;
    private $_dsnkey = null;
    private $_link = null;
    private $_prefix = '';
    private $_plink = 0;
    //return boolen variable
    private $_true_val = 1;
    private $_false_val = 0;
    private $_run_dev = true;

    public function __destruct() {
        $this->close();
    }

    public function connect($dsn, $dsnkey, $type = '') {
        if (is_null($this->_dsn)) {
            $this->_dsn = $dsn;
            $this->_dsnkey = $dsnkey;
            $this->_prefix = $dsn['prefix'];
            $this->_plink = $dsn['pconnect'];
            $this->_run_dev = $dsn['rundev'];
        }
        try {
            if (is_null($this->_link)) {
                $this->_link = new \Redis();
            }
            if ($dsn['pconnect']) {
                $server = 'pconnect';
            } else {
                $server = 'connect';
            }
            $ret = $this->_link->$server($dsn['host'], $dsn['port'], $dsn['timeout']);
            if ($ret && $dsn['password']) {
                $ret = $this->_link->auth($dsn['login'] . "-" . $dsn['password'] . "-" . $dsn['database']);
            }
            if ($ret) {
                $this->_link->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
            } else {
                $this->_link = null;
            }
        } catch (\RedisException $ex) {
            if ('RETRY' != $type) {
                return $this->reconnect();
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
        return $this->_true_val;
    }

    public function close() {
        if (!$this->_plink) {
            $this->_link && $this->_link->close();
        }
    }

    public function reconnect() {
        return $this->connect($this->_dsn, $this->_dsnkey, 'RETRY');
    }

    public function client() {
        return $this->_link;
    }

    public function get($key) {
        try {
            return $this->_link->get($key);
        } catch (\RedisException $e) {
            return $this->_halt($e->getMessage(), $e->getCode());
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
            return $this->_halt($e->getMessage(), $e->getCode());
        }
    }

    public function rm($key) {
        try {
            return $this->_link->delete($key);
        } catch (\RedisException $e) {
            return $this->_halt($e->getMessage(), $e->getCode());
        }
    }

    public function clear() {
        try {
            return $this->_link->flushDB();
        } catch (\RedisException $e) {
            return $this->_halt($e->getMessage(), $e->getCode());
        }
    }

    private function _halt($message = '', $code = 0) {
        if ($this->_run_dev) {
            $this->close();
            throw new Exception\DbException($message, $code);
        }
        return $this->_false_val;
    }
}
