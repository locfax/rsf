<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Redis {

    private $_config = null;
    private $_link = null;

    public function __destruct() {
        $this->close();
    }

    /**
     * @param $func
     * @param $args
     * @return mixed
     */
    public function __call($func, $args) {
        return call_user_func_array(array($this->_link, $func), $args);
    }

    /**
     * @param $config
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
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
            if ($connect) {
                if ($config['password']) {
                    $this->_link->auth($config['login'] . "-" . $config['password'] . "-" . $config['database']);
                }
                $this->_link->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
            }
            return true;
        } catch (\RedisException $ex) {
            if ('RETRY' !== $type) {
                return $this->reconnect();
            }
            $this->_link = null;
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    public function close() {
        if (!$this->_config['pconnect']) {
            $this->_link && $this->_link->close();
        }
    }

    /**
     * @return bool
     */
    public function reconnect() {
        return $this->connect($this->_config, 'RETRY');
    }

    /**
     * @param string $message
     * @param int $code
     * @return bool
     * @throws Exception\DbException
     */
    private function _halt($message = '', $code = 0) {
        if ($this->_config['rundev']) {
            $this->close();
            throw new Exception\DbException($message, $code);
        }
        return false;
    }
}
