<?php

namespace Rsf;

class Db {

    private static $default_dbo_id = APPDSN;
    private static $using_dbo_id = null;
    private static $used_dbo = [];

    /**
     * @param string $dsnid
     * @return null
     * @throws Exception
     */
    public static function dbo($dsnid = 'portal') {
        $_dsn = \Context::dsn($dsnid);
        $dsnkey = md5($_dsn['driver'] . '_' . $_dsn['dsn']); //连接池key
        if (isset(self::$used_dbo[$dsnkey])) {
            $dbo = self::$used_dbo[$dsnkey];
            $dbo->connect($_dsn);
        } else {
            $classname = '\\db\\' . ucfirst($_dsn['driver']);
            $dbo = new $classname;
            $dbo->connect($_dsn);
            self::$used_dbo[$dsnkey] = $dbo;
        }
        return $dbo;
    }

    public static function close() {
        $dbos = self::$used_dbo;
        if (!empty($dbos)) {
            foreach ($dbos as $dbo) {
                $dbo->close();
            }
        }
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
     * field => string, 内镶文档所在字段
     * query=>array, //条件
     * sort=>string //内镶文档无效
     * )
     * @param $field
     * @param $condition
     * @param int $length
     * @param int $pageparm
     * @param bool $yield
     * @return array
     */
    public static function page($table, $field, $condition, $pageparm = 0, $length = 18, $yield = true) {
        $db = self::Using(self::$using_dbo_id);
        if (is_array($pageparm)) {
            //固定长度分页模式
            $ret = [
                'rowsets' => [],
                'pagebar' => ''
            ];
            if ($pageparm['totals'] <= 0) {
                return $ret;
            }
            $start = self::page_start($pageparm['curpage'], $length, $pageparm['totals']);
            $data = $db->page($table, $field, $condition, $start, $length, $yield);
            if (!isset($pageparm['type']) || 'pagebar' == $pageparm['type']) {
                $defpageparm = [
                    'curpage' => 1,
                    'maxpages' => 0,
                    'showpage' => 10,
                    'udi' => '',
                    'shownum' => false,
                    'showkbd' => false,
                    'simple' => false
                ];
                $pageparm = array_merge($defpageparm, $pageparm);
                $pageparm['length'] = $length;
                $pagebar = Helper\Pager::getInstance()->pagebar($pageparm);
            } elseif ('simplepage' == $pageparm['type']) {
                $defpageparm = [
                    'curpage' => 1,
                    'udi' => ''
                ];
                $pageparm = array_merge($defpageparm, $pageparm);
                $pageparm['length'] = $length;
                $pagebar = Helper\Pager::getInstance()->simplepage($pageparm);
            } else {
                $pagebar = [
                    'totals' => $pageparm['totals'],
                    'pages' => ceil($pageparm['totals'] / $length),
                    'curpage' => $pageparm['curpage']
                ];
            }
            $ret['rowsets'] = $data;
            $ret['pagebar'] = $pagebar;
            return $ret;
        } else {
            //任意长度模式
            $start = $pageparm;
            $data = $db->page($table, $field, $condition, $start, $length, $yield);
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
        return $db->findOne($table, $field, $condition);
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
    public static function findAll($table, $field = '*', $condition = '1', $yield = false) {
        $db = self::Using(self::$using_dbo_id);
        return $db->findAll($table, $field, $condition, $yield);
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
    public static function count($table, $condition, $field = '*') {
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


    //--------------多表联合查询---start---------------//

    /**
     * @param $sql
     * @param int $pageparm
     * @param int $length
     * @return array
     */
    public static function pages($sql, $pageparm = 0, $length = 18) {
        $db = self::Using(self::$using_dbo_id);
        if (is_array($pageparm)) {
            //固定长度分页模式
            $ret = [
                'rowsets' => [],
                'pagebar' => ''
            ];
            if ($pageparm['totals'] <= 0) {
                return $ret;
            }
            $start = self::page_start($pageparm['curpage'], $length, $pageparm['totals']);
            $data = $db->pages($sql . " LIMIT {$start},{$length}");
            if (!isset($pageparm['type']) || 'pagebar' == $pageparm['type']) {
                $defpageparm = [
                    'curpage' => 1,
                    'maxpages' => 0,
                    'showpage' => 10,
                    'udi' => '',
                    'shownum' => false,
                    'showkbd' => false,
                    'simple' => false
                ];
                $pageparm = array_merge($defpageparm, $pageparm);
                $pageparm['length'] = $length;
                $pagebar = Helper\Pager::getInstance()->pagebar($pageparm);
            } elseif ('simplepage' == $pageparm['type']) {
                $defpageparm = [
                    'curpage' => 1,
                    'udi' => ''
                ];
                $pageparm = array_merge($defpageparm, $pageparm);
                $pageparm['length'] = $length;
                $pagebar = Helper\Pager::getInstance()->simplepage($pageparm);
            } else {
                $pagebar = [
                    'totals' => $pageparm['totals'],
                    'pages' => ceil($pageparm['totals'] / $length),
                    'curpage' => $pageparm['curpage']
                ];
            }
            $ret['rowsets'] = $data;
            $ret['pagebar'] = $pagebar;
            return $ret;
        } else {
            //任意长度模式
            $start = $pageparm;
            $data = $db->pages($sql . " LIMIT {$start},{$length}");
            return $data;
        }
    }


    /**
     * @param $query
     * @return mixed
     */
    public static function one($query) {
        $db = self::Using(self::$using_dbo_id);
        return $db->one($query);
    }

    /**
     * @param $query
     * @return mixed
     */
    public static function all($query) {
        $db = self::Using(self::$using_dbo_id);
        return $db->all($query);
    }

    //--------------多表联合查询--------end----------//

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

}