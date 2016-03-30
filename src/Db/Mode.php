<?php

namespace Rsf\Db;

class Model {

    use \Rsf\Base\Singleton;

    private $db = null;
    private $sql = ''; //sql语句，主要用于输出构造成的sql语句
    private $options = array(); // 查询表达式参数

    public function init($db) {
        $this->db = $db;
        return $this;
    }

    public function table($table) {
        $this->options['table'] = $table;
        return $this;
    }


    /**
     * @param $method
     * @param $args
     * @return $this
     * @throws \Exception
     */
    public function __call($method, $args) {
        $_method = strtolower($method);
        if (in_array($_method, array('field', 'data', 'where', 'group', 'having', 'order', 'limit'))) {
            $this->options[$_method] = $args[0]; //接收数据
            if (!$this->options['field']) {
                $this->options['field'] = '*';
            }
            return $this; //返回对象，连贯查询
        } else {
            throw new \Rsf\Exception\Exception($_method . '方法没有定义', 0);
        }
    }

    /**
     * 统计行数
     * @return Ambigous <boolean, unknown>|unknown
     */
    public function count($field = '*') {
        $table = $this->options['table']; //当前表
        $where = $this->_parse_condition(); //条件
        return $this->db->count($table, $where, $field);
    }

    /**
     * 只查询一条信息，返回一维数组
     * @return boolean
     */
    public function one() {
        $table = $this->options['table']; //当前表
        $field = $this->options['field']; //字段
        $_field = $field ? $field : '*';
        $where = $this->_parse_condition(); //条件
        $this->sql = "SELECT {$_field} FROM %s WHERE $where";
        $data = $this->db->findOne($table, $this->sql);
        return $data;
    }

    /**
     * 查询多条信息，返回数组
     */
    public function all() {
        $table = $this->options['table']; //当前表
        $field = $this->options['field']; //查询的字段
        $_field = $field ? $field : '*';
        $where = $this->_parse_condition(); //条件
        $this->sql = "SELECT {$_field} FROM %s WHERE {$where}";
        return $this->db->findAll($table, $this->sql);
    }

    /**
     * 获取一张表的所有字段
     */
    public function fields() {
        $table = $this->options['table'];
        $data = $this->db->fields($table);
        return $data;
    }


    /**
     * 插入数据
     * @param bool $replace
     * @return mixed
     */
    public function save($replace = false) {
        $table = $this->options['table']; //当前表
        $data = $this->options['data'];
        if ($replace) {
            return $this->db->replace($table, $data);
        } else {
            return $this->db->create($table, $data);
        }
    }

    /**
     * 修改更新
     * @return boolean
     */
    public function update() {
        $table = $this->options['table']; //当前表
        $data = $this->options['data']; //要更新的数据
        $condition = $this->_parse_condition(); //更新条件
        return $this->db->update($table, $data, $condition);
    }

    /**
     * 删除
     * @return boolean
     */
    public function delete() {
        $table = $this->options['table']; //当前表
        $condition = $this->_parse_condition(); //条件
        return $this->db->remove($table, $condition);
    }

    /**
     * 返回sql语句
     * @return string
     */
    public function getsql() {
        return $this->sql;
    }

    /**
     * 解析条件
     * @return unknown
     */
    private function _parse_condition() {
        $condition = $this->parse_condition($this->options);
        $this->options['where'] = '';
        $this->options['group'] = '';
        $this->options['having'] = '';
        $this->options['order'] = '';
        $this->options['limit'] = '';
        $this->options['field'] = '*';
        return $condition;
    }

    //解析查询条件
    public function parse_condition($options) {
        $condition = "";
        if (!empty($options['where'])) {
            if (is_string($options['where'])) {
                $condition = $options['where'];
            } else if (is_array($options['where'])) {
                $condition = $this->db->field_value($options['where'], ' AND ');
            }
        }
        if (!empty($options['group']) && is_string($options['group'])) {
            $condition .= " GROUP BY " . $options['group'];
        }
        if (!empty($options['having']) && is_string($options['having'])) {
            $condition .= " HAVING " . $options['having'];
        }
        if (!empty($options['order']) && is_string($options['order'])) {
            $condition .= " ORDER BY " . $options['order'];
        }
        if (!empty($options['limit']) && (is_string($options['limit']) || is_numeric($options['limit']))) {
            $condition .= " LIMIT " . $options['limit'];
        }
        return $condition;
    }

}
