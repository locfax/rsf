<?php

namespace Rsf\Db;

class redis {

    use \Rsf\Base\Singleton;

    //dsn information
    private $_dsn = null;
    private $_dsnkey = null;
    private $_plink = null;
    private $_link = null;
    private $_schema = null;
    private $_false_val = 0;
    private $_run_dev = true;

    public function __destruct() {
        $this->close();
    }

    public function connect($dsn, $dsnkey, $type = '') {
        static $linkpool = array();
        if ('' === $type && isset($linkpool[$dsnkey])) {
            //如果已经尝试连接过
            if ($dsn['database'] === $linkpool[$dsnkey]) {
                return;
            }
        }
        $linkpool[$dsnkey] = $dsn['database'];

        if (is_null($this->_dsn)) {
            $this->_dsn = $dsn;
            $this->_dsnkey = $dsnkey;
            $this->_schema = $dsn['database'];
            $this->_run_dev = $dsn['rundev'];
            $this->_plink = $dsn['pconnect'];
        }
        try {
            $this->_link = new \Redis();
            if ($dsn['pconnect']) {
                $ret = $this->_link->pconnect($dsn['host'], $dsn['port'], $dsn['timeout'], $dsn['database']);
            } else {
                $ret = $this->_link->connect($dsn['host'], $dsn['port'], $dsn['timeout']);
            }
            if ($ret && $dsn['password']) {
                $this->_link->auth($dsn['login'] . "-" . $dsn['password'] . "-" . $dsn['database']);
            }
            $this->_link->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        } catch (\RedisException $ex) {
            if ('RETRY' != $type) {
                $this->connect($dsn, $dsnkey, 'RETRY');
            } else {
                unset($linkpool[$dsnkey]);
                $this->_link = null;
                $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            }
        }
    }

    public function select($dbid) {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            $this->_link->select($dbid);
        } catch (\RedisException $ex) {
            $this->_link = null;
        }
    }

    function close() {
        $this->_link = null;
    }

    function get($key) {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            return $this->_link->get($key);
        } catch (\RedisException $ex) {
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    function expire($key, $ttl) {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            if ($ttl > 0) {
                $this->_link->expire($key, $ttl);
            }
        } catch (\RedisException $ex) {
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    function set($key, $value, $ttl = 0) {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            $ret = $this->_link->set($key, $value);
            if ($ttl > 0) {
                $this->_link->expire($key, $ttl);
            }
            return $ret;
        } catch (\RedisException $ex) {
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    function rm($key) {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            return $this->_link->del($key);
        } catch (\RedisException $ex) {
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    function clear() {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            return $this->_link->flushDB();
        } catch (\RedisException $ex) {
            return $this->_false_val;
        }
    }

    private function __halt($message = '', $data = '', $halt = 0) {
        if ($halt) {
            throw new \Rsf\Exception\Exception($message, $data);
        } else {
            return $this->_false_val;
        }
    }

}
