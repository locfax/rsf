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
            $connect = $this->_link->$server($dsn['host'], $dsn['port'], $dsn['timeout']);
            if ($connect && $dsn['password']) {
                $connect = $this->_link->auth($dsn['login'] . "-" . $dsn['password'] . "-" . $dsn['database']);
            }
            if ($connect) {
                $this->_link->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
            }
        } catch (\RedisException $ex) {
            if ('RETRY' != $type) {
                return $this->reconnect();
            }
            $this->_link = null;
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
        return true;
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

    private function _halt($message = '', $code = 0) {
        if ($this->_run_dev) {
            $this->close();
            throw new Exception\DbException($message, $code);
        }
        return false;
    }
}
