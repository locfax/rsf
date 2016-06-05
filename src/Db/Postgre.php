<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Postgre {

    private $_config = null;
    private $_link = null;

    public function __destruct() {
        $this->close();
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
            $this->_link = pg_connect('host=' . $config['host'] . ' port=' . $config['port'] . ' user=' . $config['login'] . ' password=' . $config['password'] . ' dbname=' . $config['database']);
            pg_set_client_encoding($this->_link, $config['charset']);
            return true;
        } catch (\ErrorException $e) {
            if ('RETRY' !== $type) {
                return $this->reconnect();
            }
            $this->_link = null;
            return $this->_halt($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return bool
     */
    public function reconnect() {
        return $this->connect($this->_config, 'RETRY');
    }

    public function close() {
        $this->_link && pg_close($this->_link);
    }

    /**
     * @param $sql
     * @return bool|null|resource
     * @throws Exception\DbException
     */
    public function query($sql) {
        if (!$this->_link) {
            return false;
        }
        try {
            $query = pg_query($this->_link, $sql);
            if ($query) {
                return $query;
            }
            return $query;
        } catch (\ErrorException $e) {
            $query = null;
            $this->_halt($e->getMessage(), $e->getCode());
            return false;
        }
    }

    /**
     * @param $value
     * @return int|string
     */
    public function qstr($value) {
        static $exist_escape_string = false;
        if (!$exist_escape_string && function_exists('pg_escape_string')) {
            $exist_escape_string = true;
        }
        if (is_numeric($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? 1 : 0;
        } elseif (is_null($value)) {
            return 'NULL';
        }
        if ($exist_escape_string && $this->_link) {
            $return = pg_escape_string($this->_link, $value);
        } else {
            $return = $value;
        }
        return "'" . $return . "'";
    }

    /**
     * @param $fieldName
     * @return string
     */
    public function qfield($fieldName) {
        $_fieldName = trim($fieldName);
        $ret = ('*' == $_fieldName) ? '*' : "`{$_fieldName}`";
        return $ret;
    }

    /**
     * @param $tableName
     * @param string $alias
     * @return string
     */
    public function qtable($tableName, $alias = '') {
        if (strpos($tableName, '.')) {
            $parts = explode('.', $tableName);
            $tableName = trim($parts[1]);
            $schema = trim($parts[0]);
        } else {
            $tableName = $this->_config['prefix'] . trim($tableName);
            $schema = $this->_config['database'];
        }
        $_alias = $alias ? " AS {$alias}" : '';
        $ret = "`{$schema}`.`{$tableName}`" . $_alias;
        return $ret;
    }

    /**
     * @param array $fields
     * @param string $glue
     * @return string
     */
    public function field_value($fields = [], $glue = ',') {
        $sql = $comma = '';
        foreach ($fields as $field => $value) {
            $sql .= $comma . $this->qfield($field) . '=' . $this->qstr($value);
            $comma = $glue;
        }
        return $sql;
    }

    /**
     * @param $tableName
     * @param $data
     * @param bool $retid
     * @return bool|null|resource
     */
    public function create($tableName, $data, $retid = false) {
        if (empty($data)) {
            return false;
        }
        $fields = $values = $comma = '';
        foreach ($data as $field => $value) {
            $fields .= $comma . $this->qfield($field);
            $values .= $comma . $this->qstr($value);
            $comma = ',';
        }
        //RETURNING id pg8.2+
        if ($retid) {
            $sql = 'INSERT INTO ' . $this->qtable($tableName) . '(' . $fields . ') VALUES (' . $values . ') RETURNING ' . $retid;
        } else {
            $sql = 'INSERT INTO ' . $this->qtable($tableName) . '(' . $fields . ') VALUES (' . $values . ')';
        }
        $ret = $this->query($sql);
        return $ret;
    }

    /**
     * @param $tableName
     * @param $data
     * @return bool|null|resource
     */
    public function replace($tableName, $data) {
        if (empty($data)) {
            return false;
        }
        $fields = $values = $comma = '';
        foreach ($data as $field => $value) {
            $fields .= $comma . $this->qfield($field);
            $values .= $comma . $this->qstr($value);
            $comma = ',';
        }
        $ret = $this->query('REPLACE INTO ' . $this->qtable($tableName) . '(' . $fields . ') VALUES (' . $values . ')');
        return $ret;
    }

    /**
     * @param $tableName
     * @param $data
     * @param $condition
     * @param bool $retnum
     * @return bool|null|resource
     */
    public function update($tableName, $data, $condition, $retnum = false) {
        if (empty($data)) {
            return false;
        }
        if (is_array($data)) {
            $data = $this->field_value($data, ',');
        }
        if (is_array($condition)) {
            $where = $this->field_value($condition, ' AND ');
        } else {
            $where = $condition;
        }
        $ret = $this->query("UPDATE " . $this->qtable($tableName) . " SET $data WHERE $where");
        return $ret;
    }

    /**
     * @param $tableName
     * @param $condition
     * @param bool $muti
     * @return bool|null|resource
     */
    public function remove($tableName, $condition, $muti = false) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($condition)) {
            $condition = $this->field_value($condition, ' AND ');
        }
        $addsql = $muti ? '' : ' LIMIT 1';
        $ret = $this->query('DELETE FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . $addsql);
        return $ret;
    }

    /**
     * @param $tableName
     * @param $query
     * @param null $xoption
     * @return array|bool
     */
    public function findOne($tableName, $query, $xoption = null) {
        if (!is_resource($query)) {
            if (!strpos($query, 'LIMIT') && !strpos($query, 'limit')) {
                $query .= ' LIMIT 0,1';
            }
            if ($tableName) {
                $query = sprintf($query, $this->qtable($tableName));
            }
            $query = $this->query($query);
        }
        if (!$query) {
            return false;
        }
        $row = pg_fetch_array($query, null, PGSQL_ASSOC);
        pg_free_result($query);
        return $row;
    }

    /**
     * @param $tableName
     * @param $query
     * @param null $xoption
     * @param bool $yield
     * @return array|bool|\Generator
     */
    public function findAll($tableName, $query, $xoption = null, $yield = false) {
        if (!is_resource($query)) {
            if ($tableName) {
                if ($query) {
                    $query = str_replace('%s', $this->qtable($tableName), $query);
                } else {
                    $query = 'SELECT * FROM ' . $this->qtable($tableName);
                }
            }
            $query = $this->query($query);
        }
        if (!$query) {
            return false;
        }
        if ($yield) {
            $rowsets = $this->iterator($query);
        } else {
            $rowsets = pg_fetch_all($query);
            pg_free_result($query);
        }
        return $rowsets;
    }

    /**
     * @param $tableName
     * @param $query
     * @param int $offset
     * @param int $length
     * @param bool $yield
     * @return array|bool|\Generator
     */
    public function page($tableName, $query, $offset = 0, $length = 20, $yield = false) {
        if (!is_resource($query)) {
            if ($tableName) {
                $query = str_replace('%s', $this->qtable($tableName), $query);
            }
            if ($length) {
                $query = $query . " LIMIT {$offset}, {$length}";
            } else {
                $query = $query . " LIMIT {$offset}, 4294967294";  //4294967294 索引的最大值
            }
            $query = $this->query($query);
        }
        if (!$query) {
            return false;
        }
        if ($yield) {
            $rowsets = $this->iterator($query);
        } else {
            $rowsets = pg_fetch_all($query);
            pg_free_result($query);
        }
        return $rowsets;
    }

    /**
     * @param $query
     * @return \Generator
     */
    private function iterator($query) {
        while ($row = pg_fetch_array($query, null, PGSQL_ASSOC)) {
            yield $row;
        }
        pg_free_result($query);
    }

    /**
     * @param $tableName
     * @param $field
     * @param $condition
     * @return bool
     */
    public function result_first($tableName, $field, $condition) {
        if (is_array($condition)) {
            $where = 'WHERE ' . $this->field_value($condition, ' AND ');
        } else {
            $where = $condition ? "WHERE {$condition}" : '';
        }
        $query = $this->query("SELECT {$field} FROM " . $this->qtable($tableName) . " {$where} LIMIT 0,1");
        if (!$query) {
            return false;
        }
        $ret = pg_fetch_row($query);
        pg_free_result($query);
        if ($ret) {
            return $ret[0];
        }
        return false;
    }

    /**
     * @param $tableName
     * @param string $condition
     * @param string $field
     * @return bool
     */
    public function count($tableName, $condition = '', $field = '*') {
        return $this->result_first($tableName, "COUNT({$field})", $condition);
    }

    public function version() {
        return 'postgre null';
    }

    public function start_trans() {
        $this->_link->begintransaction();
    }

    /**
     * @param bool $commit_no_errors
     */
    public function end_trans($commit_no_errors = true) {
        if ($commit_no_errors) {
            $this->_link->commit();
        } else {
            $this->_link->rollback();
        }
    }

    /**
     * @param string $message
     * @param string $code
     * @return bool
     * @throws Exception\DbException
     */
    private function _halt($message = '', $code = '') {
        if ($this->_config['rundev']) {
            $this->close();
            throw new Exception\DbException($message, $code);
        }
        return false;
    }

}
