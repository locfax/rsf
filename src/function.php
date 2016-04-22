<?php

//keypath  path1/path2/path3
function getini($key) {
    $_CFG = Rsf\Context::mergeVars('cfg');
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

/**
 * 有模型的缓存  model/data/*.php
 * @param $cachekey
 * @param bool $reset
 * @return bool|mixed|void
 */
function datacache($cachekey, $reset = false) {
    if (!$cachekey) {
        return false;
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
        return cache('set', $cachekey, output_json($data));
    }
}

//普通级别缓存
function cache($cmd, $key = '', $val = '', $ttl = 0) {
    if (in_array($cmd, ['set', 'get', 'rm', 'clear', 'close'])) {
        $cacher = \Rsf\Cacher::getInstance();
        switch ($cmd) {
            case 'get':
                return $cacher->get($key);
            case 'set':
                return $cacher->set($key, $val, $ttl);
            case 'rm':
                return $cacher->rm($key);
            case 'clear':
                return $cacher->clear();
            case 'close':
                return $cacher->close();
        }
    }
    return false;
}

//加载系统级别缓存
function loadcache($cachename, $reset = false) {
    if (!$cachename) {
        return null;
    }
    $data = sysdata($cachename, $reset);
    if ('settings' === $cachename && $data) {
        \Rsf\Context::mergeVars('cfg', ['settings' => json_decode($data, true)]);
        return true;
    }
    return json_decode($data, true);
}

/**
 * 系统级别缓存数据
 * @param $cachename
 * @param $reset
 * @return array
 */

function sysdata($cachename, $reset = false) {
    $lost = null;
    if ($reset) {
        $lost = $cachename; //强制设置为没取到
        $data = '[]';
    } else {
        $data = cache('get', 'sys_' . $cachename);
        if (!$data) {
            $lost = $cachename;  //未取到数据
        }
    }
    if (is_null($lost)) {
        return $data; //取到全部数据 则返回
    }
    return \Model\SysData::lost($lost, $reset);
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
        \Rsf\Template::getInstance()->parse(getini('data/_view'), $tpldir, $maintpl, $cachefile, $file);
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
    $_file = $file;
    //$skin = $templateid ? $templateid : getini('settings/defskin');
    $_tplid = getini('site/themes');
    $tplfile = $_tplid . '/' . $_file . '.htm';
    if ($gettplfile) {
        return $tplfile;
    }
    $cachefile = strtolower(APPKEY) . '_' . $_tplid . '_' . str_replace('/', '_', $_file) . '_tpl.php';
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
    return $url;
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
