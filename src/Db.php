<?php

namespace Rsf;

class Db {

    private static $default_dbo_id = APPDSN;
    private static $using_dbo_id = null;

    /**
     * @param string $dsnid
     * @return null
     * @throws Exception
     */
    public static function dbo($dsnid = 'portal') {
        $_dsn = Context::dsn($dsnid);
        //连接池key
        // mysqli  驱动使用host+login作为key注册dbo对象
        //mongo pdo postgre redis使用host+login+dbname
        $driver = $_dsn['driver'];
        if ($driver == 'mysqli') {
            $dsnkey = $driver . '_' . $_dsn['host'] . '_' . $_dsn['login'] . '_' . $_dsn['port'];
        } else {
            $dsnkey = $driver . '_' . $_dsn['host'] . '_' . $_dsn['login'] . '_' . $_dsn['port'] . '_' . $_dsn['database'];
        }
        $classname = '\\Rsf\\Db\\' . ucfirst($driver);
        $dbo = $classname::getInstance();
        $dbo->connect($_dsn, $dsnkey);
        return $dbo;
    }

    /**
     * 还原默认数据源对象
     * @return null
     */
    public static function resume() {
        self::$using_dbo_id = self::$default_dbo_id;
    }

    /**
     * 插入一条数据
     * $option bool 是否返回插入的ID
     *
     * @param string $table
     * @param array $data
     * @param bool $option
     * @return bool/int
     */
    public static function create($table, $data, $option = false) {
        $db = self::Using(self::$using_dbo_id);
        return $db->create($table, $data, $option);
    }

    /**
     * 替换一条数据
     * PS:需要设置主键值
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public static function replace($table, $data) {
        $db = self::Using(self::$using_dbo_id);
        return $db->replace($table, $data);
    }

    /**
     * 更新符合条件的数据
     * @param mixed $option 是个多用途参数
     *  - mysql的情况: bool : true 返回影响数,如果是0表示无修改  false: 执行情况 返回 bool
     *  - mongod的情况: option string set ...
     *
     * @param string $table
     * @param mixed $data (array string)
     * @param mixed $condition (array string)
     * @return bool/int
     */
    public static function update($table, $data, $condition, $option = false) {
        $db = self::Using(self::$using_dbo_id);
        return $db->update($table, $data, $condition, $option);
    }

    /**
     * 删除符合条件的项
     * @param mixed $muti
     *  - mysql的情况: bool true 删除多条 返回影响数 false: 只能删除一条
     *  - mongod的情况: bool true 删除多条 false 只能删除一条
     *
     * @param string $table
     * @param mix $condition
     * @return bool/int
     */
    public static function remove($table, $condition, $muti = true) {
        $db = self::Using(self::$using_dbo_id);
        return $db->remove($table, $condition, $muti);
    }

     /**
     * 带分页数据的DB::all
     * @param string table
     * @param mixed $query
     * - mysql: string 查询字符串  完整的SQL语句
     * - mongo: array
     * array(
     * type => string // fields field
     * fields => array, //内镶文档无效
     * field => string, 内镶文档所在字段
     * query=>array, //条件
     * sort=>string //内镶文档无效
     * )
     *
     * @param $query
     * @param int $length
     * @param int $pageparm
     * @param bool $yield
     * @return array
     */
    public static function page($table, $query, $pageparm = 0, $length = 18, $yield = true) {
        $db = self::Using(self::$using_dbo_id);
        if (is_array($pageparm)) {
            //固定长度分页模式
            $ret = array(
                'rowsets' => array(),
                'pagebar' => ''
            );
            if ($pageparm['totals'] <= 0) {
                return $ret;
            }
            $offset = self::page_start($pageparm['curpage'], $length, $pageparm['totals']);
            $data = $db->page($table, $query, $offset, $length, $yield);
            if (!isset($pageparm['type']) || 'pagebar' == $pageparm['type']) {
                $defpageparm = array(
                    'curpage' => 1,
                    'maxpages' => 0,
                    'showpage' => 10,
                    'udi' => '',
                    'shownum' => false,
                    'showkbd' => false,
                    'simple' => false
                );
                $pageparm = array_merge($defpageparm, $pageparm);
                $pageparm['length'] = $length;
                $pagebar = helper\pager::getInstance()->pagebar($pageparm);
            } elseif ('simplepage' == $pageparm['type']) {
                $defpageparm = array(
                    'curpage' => 1,
                    'udi' => ''
                );
                $pageparm = array_merge($defpageparm, $pageparm);
                $pageparm['length'] = $length;
                $pagebar = helper\pager::getInstance()->simplepage($pageparm);
            } else {
                $pagebar = array(
                    'totals' => $pageparm['totals'],
                    'pages' => ceil($pageparm['totals'] / $length),
                    'curpage' => $pageparm['curpage']
                );
            }
            $ret['rowsets'] = $data;
            $ret['pagebar'] = $pagebar;
            return $ret;
        } else {
            //任意长度模式
            $offset = $pageparm;
            $data = $db->page($table, $query, $offset, $length, $yield);
            return $data;
        }
    }

    /**
     * 查找一条数据
     * 如果要链表 使用 DB::one
     *
     * @param string $table
     * @param mixed $field
     * @param mixed $condition
     * @return mixed
     */
    public static function findOne($table, $field, $condition) {
        $db = self::Using(self::$using_dbo_id);
        if (is_array($field)) {
            //mongo
            return $db->findOne($table, $field, $condition);
        } else {
            //sql
            if ($field) {
                $field = trim($field);
            } else {
                $field = '*'; //* 会消耗mysql服务器
            }
            if ($condition) {
                $sql = "SELECT {$field} FROM %s WHERE {$condition}";
            } else {
                $sql = "SELECT {$field} FROM %s";
            }
            return $db->findOne($table, $sql);
        }
    }

    /**
     * 通用取多条数据的简洁方式 如果要链表 使用 DB::all
     *
     * @param string $table
     * @param string $field
     * @param string $condition
     * @param bool $yield
     * @return mixed
     */
    public static function findAll($table, $field = '*', $condition = '', $yield = false) {
        $db = self::Using(self::$using_dbo_id);
        if (is_array($field)) {
            //mongo
            return $db->findAll($table, $field, $condition, $yield);
        } else {
            //sql
            if ($field) {
                $field = trim($field);
            } else {
                $field = '*';
            }
            if ($condition) {
                $sql = "SELECT {$field} FROM %s WHERE {$condition}";
            } else {
                $sql = "SELECT {$field} FROM %s";
            }
            return $db->findAll($table, $sql, null, $yield);
        }
    }

    /**
     * mysql 根据条件查找一条数据
     * @return array
     *
     * @param string table
     * @param mixed $query
     * mysql: string 查询字符串
     * mongo: array 字段
     *
     * @param mixed $xoption
     * mysql: bool 占位 未使用
     * mongo: array 查询条件
     */
    public static function one($table, $query, $xoption = null) {
        $db = self::Using(self::$using_dbo_id);
        return $db->findOne($table, $query, $xoption);
    }

    /**
     * mysql 根据条件查找所有符合条件的数据
     * @return  array
     *
     * @param string table
     *
     * @param mixed $query
     * mysql: string 查询字符串
     * mongo: array 字段
     *
     * @param mixed $xoption
     * mysql: string 结果数组 用字段为 key
     * mongo: array 查询条件
     *
     * @param bool $yield
     * 是否使用跌达
     */

    public static function all($table, $query, $xoption = null, $yield = false) {
        $db = self::Using(self::$using_dbo_id);
        return $db->findAll($table, $query, $xoption, $yield);
    }

    /**
     * 单表符合条件的数量
     * - mysql:
     * $field count($field)
     * 如果要连表等 就用 DB::one来实现
     * - mongo:
     * $field 无意义
     *
     * @param string $table
     * @param string $condition
     * @param string $field
     * @return mixed
     */
    public static function count($table, $condition = '', $field = '*') {
        $db = self::Using(self::$using_dbo_id);
        return $db->count($table, $condition, $field);
    }

    /**
     * sql专用
     * 返回一条数据的第一栏
     * $filed mix  需要返回的字段  或者sql语法
     *
     * @param string $table
     * @param string $filed
     * @param mixed $condition
     * @return mixed
     */
    public static function first($table, $filed, $condition) {
        $db = self::Using(self::$using_dbo_id);
        return $db->result_first($table, $filed, $condition);
    }

    /**
     * mysql query
     *
     * @param string $table
     * @param string $sql
     * @return mixed
     */
    public static function query($table, $sql) {
        $db = self::Using(self::$using_dbo_id);
        if ($table) {
            $sql = sprintf($sql, $db->qtable($table));
        }
        return $db->query($sql);
    }

    /**
     * 切换数据源对象
     *
     * @param null $id
     * @return mixed
     */
    public static function Using($id = null) {
        if (!$id) {
            //初始运行
            self::$using_dbo_id = self::$default_dbo_id;
        } else {
            //切换dbo id
            if ($id != self::$using_dbo_id) {
                self::$using_dbo_id = $id;
            }
        }
        return self::dbo(self::$using_dbo_id);
    }

    /**
     * 过滤数据表
     *
     * @param string $table
     * @param string $alias
     * @return mixed
     */
    public static function qtable($table, $alias = '') {
        $db = self::Using(self::$using_dbo_id);
        return $db->qtable($table, $alias);
    }

    /**
     * 过滤提交的数据
     *
     * @param mixed $var
     * @return mixed
     */
    public static function qstr($var) {
        $db = self::Using(self::$using_dbo_id);
        return $db->qstr($var);
    }

    /**
     * 过滤表字段
     *
     * @param mixed $var
     * @return mixed
     */
    public static function qfield($var) {
        $db = self::Using(self::$using_dbo_id);
        return $db->qfield($var);
    }

    /**
     * 数据库版本
     */

    public static function version() {
        $db = self::Using(self::$using_dbo_id);
        return $db->version();
    }

    /**
     * 整理数据表
     *
     * @param string $table
     * @return mixed
     */
    public static function optimize($table) {
        $db = self::Using(self::$using_dbo_id);
        return $db->optimize($table);
    }

    /**
     * 获取数据库的所有表
     */

    public static function tables() {
        $db = self::Using(self::$using_dbo_id);
        return $db->tables();
    }

    /**
     * 获取数据表字段
     *
     * @param string $table
     * @return mixed
     */
    public static function fields($table) {
        $db = self::Using(self::$using_dbo_id);
        return $db->fields($table);
    }

    /**
     * 获取数据库表结构
     *
     * @param string $table
     * @return mixed
     */
    public static function columns($table) {
        $db = self::Using(self::$using_dbo_id);
        return $db->columns($table);
    }

    /**
     * @param int $page
     * @param int $ppp
     * @param int $totalnum
     * @return int
     */
    public static function page_start($page, $ppp, $totalnum) {
        $totalpage = ceil($totalnum / $ppp);
        $_page = max(1, min($totalpage, intval($page)));
        return ($_page - 1) * $ppp;
    }

    /**
     * TODO
     * @param string $table
     * @return mixed
     */
    public static function M($table) {
        $db = self::Using(self::$using_dbo_id);
        $orm = Db\Model::getInstance()->init($db);
        return $orm->table($table);
    }

}
