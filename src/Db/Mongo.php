<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Mongo {

    private $_config = null;
    private $_link = null;
    private $_client = null;

    public function __destruct() {
        $this->close();
    }

    /**
     * @param $func
     * @param $args
     * @return mixed
     */
    public function __call($func, $args) {
        return call_user_func_array(array($this->_client, $func), $args);
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
            $this->_link = new \MongoClient($config['dsn'], ["connect" => false]);
            $this->_link->connect();
            $this->_client = $this->_link->selectDB($config['database']);
            return true;
        } catch (\MongoConnectionException $ex) {
            if ('RETRY' !== $type) {
                return $this->reconnect();
            }
            $this->_client = null;
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
     * @param $table
     * @param array $document
     * @param bool $retid
     * @param string $type
     * @return bool|string
     * @throws Exception\DbException
     */
    public function create($table, $document = [], $retid = false, $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            if (isset($document['_id'])) {
                if (!is_object($document['_id'])) {
                    $document['_id'] = new \MongoId($document['_id']);
                }
            } else {
                $document['_id'] = new \MongoId();
            }
            $collection = $this->_client->selectCollection($table);
            $ret = $collection->insert($document, ['w' => 1]);
            if ($retid && $ret) {
                $insert_id = (string)$document['_id'];
                return $insert_id;
            }
            return $ret['ok'];
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->create($table, $document, $retid, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param array $document
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
    public function replace($table, $document = [], $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            if (isset($document['_id'])) {
                $document['_id'] = new \MongoId($document['_id']);
            }
            $collection = $this->_client->selectCollection($table);
            $ret = $collection->save($document);
            return $ret['ok'];
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->replace($table, $document, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param array $document
     * @param array $condition
     * @param string $options
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
    public function update($table, $document = [], $condition = [], $options = 'set', $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            $collection = $this->_client->selectCollection($table);
            if (is_bool($options)) {
                $options = 'set';
            }
            if ('muti' == $options) {
                $ret = $collection->update($condition, $document);
            } elseif ('set' == $options) { //更新 字段
                $ret = $collection->update($condition, ['$set' => $document]);
            } elseif ('inc' == $options) { //递增 字段
                $ret = $collection->update($condition, ['$inc' => $document]);
            } elseif ('unset' == $options) { //删除 字段
                $ret = $collection->update($condition, ['$unset' => $document]);
            } elseif ('push' == $options) { //推入内镶文档
                $ret = $collection->update($condition, ['$push' => $document]);
            } elseif ('pop' == $options) { //删除内镶文档最后一个或者第一个
                $ret = $collection->update($condition, ['$pop' => $document]);
            } elseif ('pull' == $options) { //删除内镶文档某个值得项
                $ret = $collection->update($condition, ['$pull' => $document]);
            } elseif ('addToSet' == $options) { //追加到内镶文档
                $ret = $collection->update($condition, ['$addToSet' => $document]);
            }
            //$pushAll $pullAll
            return $ret;
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->update($table, $document, $condition, $options, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param array $condition
     * @param bool $muti
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
    public function remove($table, $condition = [], $muti = false, $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            $collection = $this->_client->selectCollection($table);
            if ($muti) {
                $ret = $collection->remove($condition);
            } else {
                $ret = $collection->remove($condition, ['justOne' => true]);
            }
            return $ret;
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->remove($table, $condition, $muti, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param array $fields
     * @param array $condition
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
    public function findOne($table, $fields = [], $condition = [], $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            $collection = $this->_client->selectCollection($table);
            $cursor = $collection->findOne($condition, $fields);
            if (isset($cursor['_id'])) {
                $cursor['_id'] = $cursor['_id']->{'$id'};
            }
            return $cursor;
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->findOne($table, $fields, $condition, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param array $fields
     * @param array $conditon
     * @param bool $yield
     * @param string $type
     * @return array|bool|\Generator
     * @throws Exception\DbException
     */
    public function findAll($table, $fields = [], $conditon = [], $yield = false, $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            $collection = $this->_client->selectCollection($table);
            if (isset($conditon['query'])) {
                $cursor = $collection->find($conditon['query'], $fields);
                if (isset($conditon['sort'])) {
                    $cursor = $cursor->sort($conditon['sort']);
                }
            } else {
                $cursor = $collection->find($conditon, $fields);
            }
            if ($yield) {
                return $this->iterator($cursor);
            } else {
                return $this->getrows($cursor);
            }
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->findAll($table, $fields, $conditon, $yield, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }


    /**
     * @param $table
     * @param array $fileds
     * @param array $conditon
     * @param int $offset
     * @param int $length
     * @param bool $yield
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
    public function page($table, $fileds = [], $conditon = [], $offset = 0, $length = 18, $yield = false, $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            $collection = $this->_client->selectCollection($table);
            if ('fields' == $conditon['type']) {
                $cursor = $collection->find($conditon['query'], $fileds);
                if (isset($conditon['sort'])) {
                    $cursor = $cursor->sort($conditon['sort']);
                }
                $cursor = $cursor->limit($length)->skip($offset);
                if ($yield) {
                    return $this->iterator($cursor);
                } else {
                    return $this->getrows($cursor);
                }
            } else {
                //内镶文档查询
                if (!$fileds) {
                    throw new Exception\DbException('fields is empty', 0);
                }
                $cursor = $collection->findOne($conditon['query'], [$fileds => ['$slice' => [$offset, $length]]]);
                return $cursor[$fileds];
            }
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->page($table, $fileds, $conditon, $offset, $length, $yield, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $cursor
     * @return \Generator
     */
    private function iterator($cursor) {
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['_id'] = $row['_id']->{'$id'};
            yield $row;
        }
    }

    /**
     * @param $cursor
     * @return array
     */
    private function getrows($cursor) {
        $rowsets = [];
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['_id'] = $row['_id']->{'$id'};
            $rowsets[] = $row;
        }
        return $rowsets;
    }

    /**
     * @param $table
     * @param array $condition
     * @param string $type
     * @return bool
     * @throws Exception\DbException
     */
    public function count($table, $condition = [], $type = '') {
        if (!$this->_client) {
            return $this->_halt('client is not connected!');
        }
        try {
            $collection = $this->_client->selectCollection($table);
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            return $collection->count($condition);
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->count($table, $condition, 'RETRY');
            }
            return $this->_halt($ex->getMessage(), $ex->getCode());
        }
    }

    public function version() {
        return 'mongo null';
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
            $message = mb_convert_encoding($message, 'utf-8', 'gbk');
            throw new Exception\DbException($message, intval($code));
        }
        return true;
    }

}
