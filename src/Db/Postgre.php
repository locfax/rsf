<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Postgre {

    //dsn information
    private $_dsn = null;
    private $_dsnkey = null;
    private $_link = null;
    private $_schema = null;
    private $_prefix = '';
    //return boolen variable
    private $_true_val = 1;
    private $_false_val = 0;
    private $_null_val = 'NULL';
    private $_run_dev = true;

    public function __destruct() {
        $this->close();
    }

    public function connect($dsn, $dsnkey, $type = '') {
        if (is_null($this->_dsn)) {
            $this->_dsn = $dsn;
            $this->_dsnkey = $dsnkey;
            $this->_schema = $dsn['database'];
            $this->_prefix = $dsn['prefix'];
            $this->_run_dev = $dsn['rundev'];
        }
        try {
            $this->_link = pg_connect('host=' . $dsn['host'] . ' port=' . $dsn['port'] . ' user=' . $dsn['login'] . ' password=' . $dsn['password'] . ' dbname=' . $dsn['database']);
            is_resource($this->_link) && pg_set_client_encoding($this->_link, $dsn['charset']);
        } catch (\ErrorException $e) {
            if ('RETRY' != $type) {
                return $this->reconnect();
            }
            $this->_link = null;
            return $this->_halt(pg_last_error());
        }
        return $this->_true_val;
    }

    public function reconnect() {
        $this->close();
        return $this->connect($this->_dsn, $this->_dsnkey, 'RETRY');
    }

    public function close() {
        $this->_link && pg_close($this->_link);
        $this->_link = null;
    }

    public function query($sql) {
        if (is_null($this->_link)) {
            return $this->_false_val;
        }
        try {
            $query = pg_query($this->_link, $sql);
        } catch (\ErrorException $e) {
            $query = null;
        }
        if ($query) {
            return $query;
        }
        return $this->_halt($this->error());
    }

    public function qstr($value) {
        static $exist_escape_string = false;
        if (!$exist_escape_string && function_exists('pg_escape_string')) {
            $exist_escape_string = true;
        }
        if (is_numeric($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? $this->_true_val : $this->_false_val;
        } elseif (is_null($value)) {
            return $this->_null_val;
        }
        if ($exist_escape_string && $this->_link) {
            $return = pg_escape_string($this->_link, $value);
        } else {
            $return = $value;
        }
        return "'" . $return . "'";
    }

    public function qfield($fieldName) {
        $_fieldName = trim($fieldName);
        $ret = ('*' == $_fieldName) ? '*' : "`{$_fieldName}`";
        return $ret;
    }

    public function qtable($tableName, $alias = '') {
        if (strpos($tableName, '.')) {
            $parts = explode('.', $tableName);
            $tableName = trim($parts[1]);
            $schema = trim($parts[0]);
        } else {
            $tableName = $this->_prefix . trim($tableName);
            $schema = $this->_schema;
        }
        $_alias = $alias ? " AS {$alias}" : '';
        $ret = "`{$schema}`.`{$tableName}`" . $_alias;
        return $ret;
    }

    public function field_value($fields = array(), $glue = ',') {
        $sql = $comma = '';
        foreach ($fields as $field => $value) {
            $sql .= $comma . $this->qfield($field) . '=' . $this->qstr($value);
            $comma = $glue;
        }
        return $sql;
    }

    /* up action is solid */

    public function create($tableName, $data, $retid = false) {
        if (empty($data)) {
            return $this->_false_val;
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

    public function replace($tableName, $data) {
        if (empty($data)) {
            return $this->_false_val;
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

    public function update($tableName, $data, $condition, $retnum = false) {
        if (empty($data)) {
            return $this->_false_val;
        }
        if (empty($condition)) {
            return $this->_false_val;
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

    public function remove($tableName, $condition, $muti = false) {
        if (empty($condition)) {
            return $this->_false_val;
        }
        if (is_array($condition)) {
            $condition = $this->field_value($condition, ' AND ');
        }
        $addsql = $muti ? '' : ' LIMIT 1';
        $ret = $this->query('DELETE FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . $addsql);
        return $ret;
    }

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
            return $this->_false_val;
        }
        $row = pg_fetch_array($query, null, PGSQL_ASSOC);
        pg_free_result($query);
        return $row;
    }

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
            return $this->_false_val;
        }
        if ($yield) {
            $rowsets = $this->iterator($query);
        } else {
            $rowsets = pg_fetch_all($query);
            pg_free_result($query);
        }
        return $rowsets;
    }

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
            return $this->_false_val;
        }
        if ($yield) {
            $rowsets = $this->iterator($query);
        } else {
            $rowsets = pg_fetch_all($query);
            pg_free_result($query);
        }
        return $rowsets;
    }

    private function iterator($query) {
        while ($row = pg_fetch_array($query, null, PGSQL_ASSOC)) {
            yield $row;
        }
        pg_free_result($query);
    }

    public function result_first($tableName, $field, $condition) {
        if (is_array($condition)) {
            $where = 'WHERE ' . $this->field_value($condition, ' AND ');
        } else {
            $where = $condition ? "WHERE {$condition}" : '';
        }
        $query = $this->query("SELECT {$field} FROM " . $this->qtable($tableName) . " {$where} LIMIT 0,1");
        if (!$query) {
            return $this->_false_val;
        }
        $ret = pg_fetch_row($query);
        pg_free_result($query);
        if ($ret) {
            return $ret[0];
        }
        return $this->_false_val;
    }

    public function count($tableName, $condition = '', $field = '*') {
        return $this->result_first($tableName, "COUNT({$field})", $condition);
    }

    public function version() {
        return '';
    }

    public function ping() {
        if (PHP_VERSION >= '4.3') {
            return pg_ping($this->_link);
        } else {
            return false;
        }
    }

    public function error() {
        $error = $this->_link ? pg_last_error($this->_link) : pg_last_error();
        return date('H:i:s') . $error;
    }

    public function errno() {
        return '';
    }

    public function start_trans() {
        $this->_link->begintransaction();
    }

    public function end_trans($commit_no_errors = true) {
        if ($commit_no_errors) {
            $this->_link->commit();
        } else {
            $this->_link->rollback();
        }
    }

    private function _halt($message = '', $data = '') {
        if ($this->_run_dev) {
            $this->close();
            throw new Exception\DbException($message, $data);
        }
        return $this->_false_val;
    }

}
