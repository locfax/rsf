<?php

//系统级别函数

/**
 * @param $variable
 * @param null $defval
 * @param string $runfunc
 * @param bool $emptyrun
 * @return null
 */
function getgpc($variable, $defval = null, $runfunc = 'daddslashes', $emptyrun = false) {
    if (1 == strpos($variable, '.')) {
        $tmp = strtoupper(substr($variable, 0, 1));
        $var = substr($variable, 2);
    } else {
        $tmp = false;
        $var = $variable;
    }
    if ($tmp) {
        switch ($tmp) {
            case 'G':
                $type = 'GET';
                if (!isset($_GET[$var])) {
                    return $defval;
                }
                $value = $_GET[$var];
                break;
            case 'P':
                $type = 'POST';
                if (!isset($_POST[$var])) {
                    return $defval;
                }
                $value = $_POST[$var];
                break;
            case 'C':
                $type = 'COOKIE';
                if (!isset($_COOKIE[$var])) {
                    return $defval;
                }
                $value = $_COOKIE[$var];
                break;
            case 'S' :
                $type = 'SERVER';
                break;
            default:
                return $defval;
        }
    } else {
        if (isset($_GET[$var])) {
            $type = 'GET';
            if (!isset($_GET[$var])) {
                return $defval;
            }
            $value = $_GET[$var];
        } elseif (isset($_POST[$var])) {
            $type = 'POST';
            if (!isset($_POST[$var])) {
                return $defval;
            }
            $value = $_POST[$var];
        } else {
            return $defval;
        }
    }
    if (in_array($type, array('GET', 'POST', 'COOKIE'))) {
        return gpc_val($value, $runfunc, $emptyrun);
    } elseif ('SERVER' == $type) {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : $defval;
    } else {
        return $defval;
    }
}

/**
 * @param $val
 * @param $runfunc
 * @param $emptyrun
 * @return string
 */
function gpc_val($val, $runfunc, $emptyrun) {
    if ('' == $val) {
        return $emptyrun ? $runfunc($val) : '';
    }
    if ($runfunc && strpos($runfunc, '|')) {
        $funcs = explode('|', $runfunc);
        array_push($funcs, 'daddslashes');
        foreach ($funcs as $run) {
            if ('xss' == $run) {
                $val = \Rsf\Helper\Xss::getInstance()->clean($val);
            } else {
                $val = $run($val);
            }
        }
        return $val;
    }
    if ('xss' == $runfunc) {
        return \Rsf\Helper\Xss::getInstance()->clean($val);
    }
    if ($runfunc) {
        return $runfunc($val);
    }
    return $val;
}

//keypath  path1/path2/path3
function getini($key) {
    $_CFG = Rsf\App::mergeVars('cfg');
    $k = explode('/', $key);
    switch (count($k)) {
        case 1:
            return isset($_CFG[$k[0]]) ? $_CFG[$k[0]] : null;
        case 2:
            return isset($_CFG[$k[0]][$k[1]]) ? $_CFG[$k[0]][$k[1]] : null;
        case 3:
            return isset($_CFG[$k[0]][$k[1]][$k[2]]) ? $_CFG[$k[0]][$k[1]][$k[2]] : null;
        case 4:
            return isset($_CFG[$k[0]][$k[1]][$k[2]][$k[3]]) ? $_CFG[$k[0]][$k[1]][$k[2]][$k[3]] : null;
        case 5:
            return isset($_CFG[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]]) ? $_CFG[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] : null;
        default:
            return null;
    }
}

//keypath  path1/path2/path3
function setcache($key, $value) {
    static $_CACHEDATA = null;
    if (is_null($_CACHEDATA)) {
        $_CACHEDATA = App::mergeVars('data');
    }
    $k = explode('/', $key);
    switch (count($k)) {
        case 1:
            $_CACHEDATA[$k[0]] = $value;
            break;
        case 2:
            $_CACHEDATA[$k[0]][$k[1]] = $value;
            break;
        case 3:
            $_CACHEDATA[$k[0]][$k[1]][$k[2]] = $value;
            break;
        case 4:
            $_CACHEDATA[$k[0]][$k[1]][$k[2]][$k[3]] = $value;
            break;
        case 5:
            $_CACHEDATA[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] = $value;
            break;
    }
    App::mergeVars('data', $_CACHEDATA);
    unset($value);
}

//keypath  path1/path2/path3
function getcache($key) {
    $_CACHEDATA = App::mergeVars('data');
    $k = explode('/', $key);
    switch (count($k)) {
        case 1:
            return isset($_CACHEDATA[$k[0]]) ? $_CACHEDATA[$k[0]] : null;
        case 2:
            return isset($_CACHEDATA[$k[0]][$k[1]]) ? $_CACHEDATA[$k[0]][$k[1]] : null;
        case 3:
            return isset($_CACHEDATA[$k[0]][$k[1]][$k[2]]) ? $_CACHEDATA[$k[0]][$k[1]][$k[2]] : null;
        case 4:
            return isset($_CACHEDATA[$k[0]][$k[1]][$k[2]][$k[3]]) ? $_CACHEDATA[$k[0]][$k[1]][$k[2]][$k[3]] : null;
        case 5:
            return isset($_CACHEDATA[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]]) ? $_CACHEDATA[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] : null;
        default:
            return null;
    }
}

/**
 * 有模型的缓存  model/data/*.php
 * @param $cachekey
 * @param bool $reset
 * @return bool|mixed|void
 */
function datacache($cachekey, $reset = false) {
    if (!$cachekey) {
        return;
    }
    if (!$reset) {
        $data = cache('get', $cachekey);
        if (is_null($data)) {
            $dataclass = '\\Model\\Data\\' . ucfirst($cachekey);
            $data = $dataclass::getInstance()->getdata();
            cache('set', $cachekey, output_json($data));
        } else {
            $data = json_decode($data, true);
        }
        return $data;
    } else {//重置缓存
        $dataclass = '\\Model\\Data\\' . ucfirst($cachekey);
        $data = $dataclass::getInstance()->getdata();
        cache('set', $cachekey, output_json($data));
    }
}

//普通级别缓存
function cache($cmd, $key = '', $val = '', $ttl = 0) {
    $cacher = \Rsf\Cache\Cacher::getInstance();
    if (in_array($cmd, array('set', 'get', 'rm', 'clear'))) {
        switch ($cmd) {
            case 'get':
                return $cacher->get($key);
            case 'set':
                return $cacher->set($key, $val, $ttl);
            case 'rm':
                return $cacher->rm($key);
            case 'clear':
                return $cacher->clear();
        }
    }
    $cacher = null;
    return false;
}

//加载系统级别缓存
function loadcache($cachenames, $return = false, $reset = false) {
    static $loadedcache = array(); //防止多次执行 function data()
    $_cachenames = is_array($cachenames) ? $cachenames : explode(',', $cachenames);
    $lostcache = array();
    foreach ($_cachenames as $k) {
        if (!isset($loadedcache[$k]) || $reset) {
            $lostcache[] = $k;
        }
    }
    if (!empty($lostcache)) {
        $cachedata = sysdata($lostcache, $reset);
        foreach ($cachedata as $cname => $json) {
            if ('settings' == $cname) {
                \Rsf\App::mergeVars('cfg', array('settings' => json_decode($json, true)));
            } else {
                setcache($cname, json_decode($json, true));
            }
            $loadedcache[$cname] = true;
        }
        $mcachedata = null;
    }
    if ($return) {
        return getcache($_cachenames[0]);
    }
}

/*
    * 系统级别缓存数据
    * @ return $data is array
    * @ $data[name] is json
    */

function sysdata($cachenames, $reset = false) {
    $data = array();
    $lostcaches = array();
    foreach ($cachenames as $name) {
        if ($reset) {
            $lostcaches[] = $name; //强制设置为没取到
        } else {
            $data[$name] = cache('get', $name);
            if (null == $data[$name]) {
                $lostcaches[] = $name; //没取到
            }
        }
    }
    if (empty($lostcaches)) {
        return $data; //取到全部数据 则返回
    }
    $data = \Model\Base\SysData::lost($data, $lostcaches, $reset);
    return $data;
}

/**
 * @param $maintpl
 * @param $subtpl
 * @param $cachetime
 * @param $cachefile
 * @param $file
 */
function checktplrefresh($maintpl, $subtpl, $cachetime, $cachefile, $file) {
    static $tplrefresh = null;
    if (null == $tplrefresh) {
        $tplrefresh = getini('data/tplrefresh') > 1 ? getini('data/tplrefresh') : 1;
    }
    if ($tplrefresh > 0 || !$cachetime) {
        $tpldir = getini('data/tpldir');
        $tpltime = filemtime($tpldir . $subtpl);
        if ($tpltime < intval($cachetime)) {
            return;
        }
        \Rsf\Base\Template::getInstance()->parse(getini('data/_view'), $tpldir, $maintpl, $cachefile, $file);
    }
}

/**
 * @param $file
 * @param string $templateid
 * @param bool $gettplfile
 * @return string
 */
function template($file, $templateid = '', $gettplfile = false) {
    if (strpos($file, ':')) {
        list($templateid, $file) = explode(':', $file, 2);
    }
    $_file = getgpc('inajax') && ('header' == $file || 'footer' == $file) ? $file . '_ajax' : $file;
    //$skin = $templateid ? $templateid : getini('settings/defskin');
    $_tplid = getini('site/themes');
    $tplfile = $_tplid . '/' . $_file . '.htm';
    if ($gettplfile) {
        return $tplfile;
    }
    $cachefile = APPKEY . '_' . $_tplid . '_' . str_replace('/', '_', $_file) . '_tpl.php';
    $cachetpl = getini('data/_view') . $cachefile;
    $cachetime = is_file($cachetpl) ? filemtime($cachetpl) : 0;
    checktplrefresh($tplfile, $tplfile, $cachetime, $cachefile, $_file);
    return $cachetpl;
}

/**
 * @param $udi
 * @return string
 */
function url($udi) {
    //$_path = getini('site/path');
    $_udis = explode('/', $udi);
    $url = '?ctl=' . $_udis[0] . '&act=' . $_udis[1];
    for ($i = 2; $i < count($_udis); $i++) {
        $url .= '&' . $_udis[$i] . '=' . $_udis[$i + 1];
        $i++;
    }
    return SITEPATH . $url;
}

/**
 * @param $udi
 * @return string
 */
function fullurl($udi) {
    //$_path = getini('site/path');
    $_udis = explode('/', $udi);
    $url = '?ctl=' . $_udis[0] . '&act=' . $_udis[1];
    for ($i = 2; $i < count($_udis); $i++) {
        $url .= '&' . $_udis[$i] . '=' . $_udis[$i + 1];
        $i++;
    }
    return 'http://' . SITEHOST . SITEPATH . $url;
}

/**
 * @param $pass
 * @param $salt
 * @param bool $md5
 * @return string
 */
function topassword($pass, $salt, $md5 = false) {
    if ($md5) {
        return md5($pass . $salt);
    } else {
        return md5(md5($pass) . $salt);
    }
}
