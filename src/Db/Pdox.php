<?php

namespace Rsf\Db;

use \Rsf\Exception;

class Pdox {

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
			$opt = array(
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $config['charset'],
				\PDO::ATTR_PERSISTENT => false
			);
			$this->_link = new \PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}", $config['login'], $config['password'], $opt);
		} catch (\PDOException $e) {
			if ('RETRY' !== $type) {
				return $this->reconnect();
			}
			$this->_link = null;
			return $this->_halt($e->getMessage(), $e->getCode());
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function reconnect() {
		return $this->connect($this->_config, 'RETRY');
	}

	public function close() {
		$this->_link = null;
	}

	/**
	 * @param $fieldName
	 * @return string
	 */
	public function qfield($fieldName) {
		$_fieldName = trim($fieldName);
		$ret = ($_fieldName == '*') ? '*' : "`{$_fieldName}`";
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
	 * @return array
	 */
	public function field_param(array $fields, $glue = ',') {
		$args = [];
		$sql = $comma = '';
		foreach ($fields as $field => $value) {
			$sql .= $comma . $this->qfield($field) . '=:' . $field;
			$args[':' . $field] = $value;
			$comma = $glue;
		}
		return [$sql, $args];
	}

	/**
	 * @param array $fields
	 * @param string $glue
	 * @return string
	 */
	public function field_value(array $fields, $glue = ',') {
		$addsql = $comma = '';
		foreach ($fields as $field => $value) {
			$addsql .= $comma . $this->qfield($field) . '=' . $value;
			$comma = $glue;
		}
		return $addsql;
	}

	/**
	 * @param $tableName
	 * @param array $data
	 * @param bool $retid
	 * @return bool
	 * @throws Exception\DbException
	 */
	public function create($tableName, array $data, $retid = false) {
		if (empty($data)) {
			return false;
		}
		$args = [];
		$fields = $values = $comma = '';
		foreach ($data as $field => $value) {
			$fields .= $comma . $this->qfield($field);
			$values .= $comma . ':' . $field;
			$args[':' . $field] = $value;
			$comma = ',';
		}
		try {
			$sth = $this->_link->prepare('INSERT INTO ' . $this->qtable($tableName) . '(' . $fields . ') VALUES (' . $values . ')');
			$ret = $sth->execute($args);
			if ($ret && $retid) {
				return $this->_link->lastInsertId();
			}
			return $ret;
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}

	public function replace($tableName, array $data) {
		if (empty($data)) {
			return false;
		}
		$args = [];
		$fields = $values = $comma = '';
		foreach ($data as $field => $value) {
			$fields .= $comma . $this->qfield($field);
			$values .= $comma . ':' . $field;
			$args[':' . $field] = $value;
			$comma = ',';
		}
		try {
			$sth = $this->_link->prepare('REPLACE INTO ' . $this->qtable($tableName) . '(' . $fields . ') VALUES (' . $values . ')');
			return $sth->execute($args);
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}

	/**
	 * @param $tableName
	 * @param array $data
	 * @param $condition
	 * @param bool $retnum
	 * @return bool
	 * @throws Exception\DbException
	 */
	public function update($tableName, array $data, $condition, $retnum = false) {
		if (empty($data)) {
			return false;
		}
		if (empty($condition)) {
			return false;
		}
		list($data, $argsf) = $this->field_param($data, ',');
		if (is_array($condition)) {
			list($condition, $argsw) = $this->field_param($condition, ' AND ');
		} else {
			$argsw = [];
		}
		$args = array_merge($argsf, $argsw);
		try {
			$sth = $this->_link->prepare("UPDATE " . $this->qtable($tableName) . " SET $data WHERE $condition");
			$ret = $sth->execute($args);
			if ($ret && $retnum) {
				return $sth->rowCount();
			}
			return $ret;
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}

	/**
	 * @param $tableName
	 * @param $condition
	 * @param bool $muti
	 * @return bool
	 * @throws Exception\DbException
	 */
	public function remove($tableName, $condition, $muti = false) {
		if (empty($condition)) {
			return false;
		}
		if (is_array($condition)) {
			$condition = $this->field_value($condition, ' AND ');
		}
		$limit = $muti ? '' : ' LIMIT 1';
		try {
			return $this->_link->exec('DELETE FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . $limit);
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}


	/**
	 * @param $tableName
	 * @param string $field
	 * @param $condition
	 * @return bool
	 * @throws Exception\DbException
	 */
	public function findOne($tableName, $field = '*', $condition) {
		try {
			if (is_array($condition)) {
				list($condition, $args) = $this->field_param($condition, ' AND ');
				$sth = $this->_link->prepare('SELECT ' . $field . ' FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . ' LIMIT 0,1');
				$sth->execute($args);
			} else {
				$sth = $this->_link->query('SELECT ' . $field . ' FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . ' LIMIT 0,1');
			}
			return $sth->fetch();
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}

	/**
	 * @param $tableName
	 * @param string $field
	 * @param $condition
	 * @param bool $yield
	 * @return bool
	 * @throws Exception\DbException
	 */
	public function findAll($tableName, $field = '*', $condition, $yield = false) {
		try {
			if (is_array($condition)) {
				list($condition, $args) = $this->field_param($condition, ' AND ');
				$sth = $this->_link->prepare('SELECT ' . $field . ' FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition);
				$sth->execute($args);
			} else {
				$sth = $this->_link->query('SELECT ' . $field . ' FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition);
			}
			return $sth->fetchAll();
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}

	/**
	 * @param $tableName
	 * @param string $field
	 * @param $condition
	 * @param int $offset
	 * @param int $length
	 * @param bool $yield
	 * @return bool
	 * @throws Exception\DbException
	 */
	public function page($tableName, $field = '*', $condition, $offset = 0, $length = 20, $yield = false) {
		try {
			if (is_array($condition)) {
				list($condition, $args) = $this->field_param($condition, ' AND ');
				$args[':offset'] = $offset;
				$args[':length'] = $length;
				$sth = $this->_link->prepare('SELECT ' . $field . ' FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . ' LIMIT :offset, :length');
				$sth->execute($args);
			} else {
				$sth = $this->_link->query('SELECT ' . $field . ' FROM ' . $this->qtable($tableName) . ' WHERE ' . $condition . " LIMIT {$offset},{$length}");
			}
			return $sth->fetchAll();
		} catch (\PDOException $e) {
			$this->_halt($e->getMessage(), $e->getCode());
			return false;
		}
	}

	/**
	 * @param $tableName
	 * @param $field
	 * @param $condition
	 * @return bool
	 */
	public function result_first($tableName, $field, $condition) {
		if (is_array($condition)) {
			list($condition, $args) = $this->field_value(field_param, ' AND ');
		}
		$sth = $this->_link->prepare("SELECT {$field} AS result FROM " . $this->qtable($tableName) . " WHERE  {$condition} LIMIT 0,1");
		$ret = $sth->execute($args);
		if ($ret) {
			return $sth->fetchColumn();
		}
		return $ret;
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
		return 'NULL';
	}

	public function start_trans() {
		$this->_link->beginTransaction();
	}

	public function end_trans($commit_no_errors = true) {
		if ($commit_no_errors) {
			$this->_link->commit();
		} else {
			$this->_link->rollBack();
		}
	}

	private function _halt($message = '', $code = 0) {
		if ($this->_config['rundev']) {
			$this->close();
			throw new \Rsf\Exception\DbException($message, $code);
		}
		return false;
	}

}