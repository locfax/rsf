<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Redis {

    private $_config = null;
    private $_link = null;

    public function __destruct() {
        $this->close();
    }

    public function connect($config, $type = '') {
        if (is_null($this->_config)) {
            $this->_config = $config;
        }
        try {
            $this->_link = new \Redis();
            if ($config['pconnect']) {
                $server = 'pconnect';
            } else {
                $server = 'connect';
            }
            $connect = $this->_link->$server($config['host'], $config['port'], $config['timeout']);
            if ($connect && $config['password']) {
                $connect = $this->_link->auth($config['login'] . "-" . $config['password'] . "-" . $config['database']);
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
        if (!$this->_config['pconnect']) {
            $this->_link && $this->_link->close();
        }
    }

    public function reconnect() {
        return $this->connect($this->_config, 'RETRY');
    }

    public function client() {
        return $this->_link;
    }

    private function _halt($message = '', $code = 0) {
        if ($this->_config['rundev']) {
            $this->close();
            throw new Exception\DbException($message, $code);
        }
        return false;
    }
}
