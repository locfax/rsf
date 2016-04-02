<?php

namespace Rsf\Db;

class Mongo {

    use \Rsf\Base\Singleton;

    //dsn information
    private $_dsn = null;
    private $_dsnkey = null;
    private $_link = null;
    private $_client = null;
    private $_prefix = '';
    private $_plink = 0;
    private $_false_val = 0;
    private $_run_dev = true;

    public function __destruct() {
        $this->close();
    }

    public function connect($dsn, $dsnkey, $type = '') {
        static $linkpool = array();
        if ('' === $type && isset($linkpool[$dsnkey]) && $this->_link) {
            if ($dsn['database'] === $linkpool[$dsnkey]) {
                return;
            }
        }
        $linkpool[$dsnkey] = $dsn['database'];

        if (is_null($this->_dsn)) {
            $this->_dsn = $dsn;
            $this->_dsnkey = $dsnkey;
            $this->_prefix = $dsn['prefix'];
            $this->_plink = $dsn['pconnect'];
            $this->_run_dev = $dsn['rundev'];
        }
        try {
            if ($dsn['password']) {
                if ($dsn['pconnect']) {
                    //\MongoClient
                    $this->_link = new \MongoClient("mongodb://{$dsn['login']}:{$dsn['password']}@{$dsn['host']}:{$dsn['port']}/{$dsn['database']}", array("connect" => false, 'persist' => $dsn['host'] . '_' . $dsn['port']));
                } else {
                    $this->_link = new \MongoClient("mongodb://{$dsn['login']}:{$dsn['password']}@{$dsn['host']}:{$dsn['port']}/{$dsn['database']}");
                }
            } else {
                if ($dsn['pconnect']) {
                    $this->_link = new \MongoClient("mongodb://{$dsn['host']}:{$dsn['port']}/{$dsn['database']}", array("connect" => false, 'persist' => $dsn['host'] . '_' . $dsn['port']));
                } else {
                    $this->_link = new \MongoClient("mongodb://{$dsn['host']}:{$dsn['port']}/{$dsn['database']}");
                }
            }
            $this->_client = $this->_link->selectDB($dsn['database']);
        } catch (\MongoConnectionException $ex) {
            if ('RETRY' != $type) {
                $this->connect($dsn, $dsnkey, 'RETRY');
            } else {
                unset($linkpool[$dsnkey]);
                $this->_link = $this->_client = null;
                $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            }
        }
    }

    public function close() {
        !$this->_plink && $this->_link && $this->_link->close();
        $this->_link = $this->_client = null;
    }

    public function reconnect() {
        $this->close();
        $this->connect($this->_dsn, $this->_dsnkey, 'RETRY');
    }

    public function qtable($tableName) {
        return $this->_prefix . $tableName;
    }

    public function create($table, $document = array(), $retid = false, $type = '') {
        if (is_null($this->_client)) {
            return $this->_false_val;
        }
        try {
            if (isset($document['_id'])) {
                if (!is_object($document['_id'])) {
                    $document['_id'] = new \MongoId($document['_id']);
                }
            } else {
                $document['_id'] = new \MongoId();
            }
            $collection = $this->_client->selectCollection($this->qtable($table));
            $ret = $collection->insert($document, array('w' => 1));
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
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function replace($table, $document = array(), $type = '') {
        if (is_null($this->_client)) {
            return $this->_false_val;
        }
        try {
            if (isset($document['_id'])) {
                $document['_id'] = new \MongoId($document['_id']);
            }
            $collection = $this->_client->selectCollection($this->qtable($table));
            $ret = $collection->save($document);
            return $ret['ok'];
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->replace($table, $document, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function update($table, $document = array(), $condition = array(), $options = 'set', $type = '') {
        if (is_null($this->_client)) {
            return $this->_false_val;
        }
        try {
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            $collection = $this->_client->selectCollection($this->qtable($table));
            if (is_bool($options)) {
                $options = 'set';
            }
            if ('muti' == $options) {
                $ret = $collection->update($condition, $document);
            } elseif ('set' == $options) { //更新 字段
                $ret = $collection->update($condition, array('$set' => $document));
            } elseif ('inc' == $options) { //递增 字段
                $ret = $collection->update($condition, array('$inc' => $document));
            } elseif ('unset' == $options) { //删除 字段
                $ret = $collection->update($condition, array('$unset' => $document));
            } elseif ('push' == $options) { //推入内镶文档
                $ret = $collection->update($condition, array('$push' => $document));
            } elseif ('pop' == $options) { //删除内镶文档最后一个或者第一个
                $ret = $collection->update($condition, array('$pop' => $document));
            } elseif ('pull' == $options) { //删除内镶文档某个值得项
                $ret = $collection->update($condition, array('$pull' => $document));
            } elseif ('addToSet' == $options) { //追加到内镶文档
                $ret = $collection->update($condition, array('$addToSet' => $document));
            }
            //$pushAll $pullAll
            return $ret;
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->update($table, $document, $condition, $options, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function remove($table, $condition = array(), $muti = false, $type = '') {
        if (is_null($this->_client)) {
            return $this->_false_val;
        }
        try {
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            $collection = $this->_client->selectCollection($this->qtable($table));
            if ($muti) {
                $ret = $collection->remove($condition);
            } else {
                $ret = $collection->remove($condition, array('justOne' => true));
            }
            return $ret;
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->remove($table, $condition, $muti, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function findOne($table, $fields = array(), $condition = array(), $type = '') {
        if (is_null($this->_client)) {
            return $this->_false_val;
        }
        try {
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            $collection = $this->_client->selectCollection($this->qtable($table));
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
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function findAll($table, $fields = array(), $query = array(), $yield = false, $type = '') {
        if (is_null($this->_client)) {
            return $this->_false_val;
        }
        try {
            $collection = $this->_client->selectCollection($this->qtable($table));
            if (isset($query['query'])) {
                $cursor = $collection->find($query['query'], $fields);
                if (isset($query['sort'])) {
                    $cursor = $cursor->sort($query['sort']);
                }
            } else {
                $cursor = $collection->find($query, $fields);
            }
            if ($yield) {
                return $this->iterator($cursor);
            } else {
                return $this->getrows($cursor);
            }
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->findAll($table, $fields, $query, $yield, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function page($table, $query = array(), $offset = 0, $length = 18, $yield = false, $type = '') {
        if (!$this->_client) {
            throw new \MongoConnectionException('no conected!');
        }
        try {
            $collection = $this->_client->selectCollection($this->qtable($table));
            if ('fields' == $query['type']) {
                $cursor = $collection->find($query['query'], $query['fields']);
                if (isset($query['sort'])) {
                    $cursor = $cursor->sort($query['sort']);
                }
                $cursor = $cursor->limit($length)->skip($offset);
                if ($yield) {
                    return $this->iterator($cursor);
                } else {
                    return $this->getrows($cursor);
                }
            } else {
                //内镶文档查询
                if (!$query['field']) {
                    throw new \Rsf\Exception\Exception('fields is empty', 0);
                }
                $cursor = $collection->findOne($query['query'], array($query['field'] => array('$slice' => array($offset, $length))));
                return $cursor[$query['field']];
            }
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->page($table, $query, $offset, $length, $yield, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    private function iterator($cursor) {
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['_id'] = $row['_id']->{'$id'};
            yield $row;
        }
    }

    private function getrows($cursor) {
        $rowsets = array();
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['_id'] = $row['_id']->{'$id'};
            $rowsets[] = $row;
        }
        return $rowsets;
    }

    public function count($table, $condition = array(), $type = '') {
        if (!$this->_client) {
            throw new \MongoConnectionException('no conected!');
        }
        try {
            $collection = $this->_client->selectCollection($this->qtable($table));
            if (isset($condition['_id'])) {
                $condition['_id'] = new \MongoId($condition['_id']);
            }
            return $collection->count($condition);
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->count($table, $condition, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function drop($table, $type = '') {
        if (!$this->_client) {
            throw new \MongoConnectionException('no conected!');
        }
        try {
            $collection = $this->_client->selectCollection($this->qtable($table));
            $collection->drop();
        } catch (\MongoException $ex) {
            if ('RETRY' !== $type) {
                $this->reconnect();
                return $this->drop($table, 'RETRY');
            }
            $this->_run_dev && $this->__halt($ex->getMessage(), $ex->getCode(), $this->_run_dev);
            return $this->_false_val;
        }
    }

    public function client() {
        return $this->_client;
    }

    public function error() {
        if (method_exists($this->_client, "lastError")) {
            return $this->_client->lastError();
        }
        return '';
    }

    public function version() {
        if (class_exists('\\MongoClient')) {
            return \MongoClient::VERSION;
        }
        return '';
    }

    private function __halt($message = '', $code = '', $halt = 0) {
        if ($halt) {
            $this->close();
            throw new \Rsf\Exception\Exception($message, $code);
        } else {
            return false;
        }
    }

}
