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
 * @return bool|mixed|string
 */
function modealdata($cachekey, $reset = false) {
    if (!$cachekey) {
        return false;
    }
    if (!$reset) {
        $data = \Rsf\Context::cache('get', $cachekey);
        if (is_null($data)) {
            $dataclass = '\\Model\\Data\\' . ucfirst($cachekey);
            $data = $dataclass::getInstance()->getdata();
            \Rsf\Context::cache('set', $cachekey, output_json($data));
        } else {
            $data = json_decode($data, true);
        }
        return $data;
    } else {//重置缓存
        $dataclass = '\\Model\\Data\\' . ucfirst($cachekey);
        $data = $dataclass::getInstance()->getdata();
        return \Rsf\Context::cache('set', $cachekey, output_json($data));
    }
}

/**
 * @param $maintpl
 * @param $subtpl
 * @param $cachetime
 * @param $cachefile
 * @param $file
 */
function checktplrefresh($maintpl, $subtpl, $cachetime, $cachefile, $file) {
    $tpldir = getini('data/tpldir');
    if (is_file($tpldir . $subtpl)) {
        $tpltime = filemtime($tpldir . $subtpl);
    } else {
        $tpltime = 0;
    }
    if ($tpltime < intval($cachetime)) {
        return;
    }
    \Rsf\Template::getInstance()->parse(getini('data/_view'), $tpldir, $maintpl, $cachefile, $file);
}

/**
 * @param $file
 * @return string
 */
function template($file) {
    $_tplid = getini('site/themes');
    $tplfile = $_tplid . '/' . $file . '.htm';
    $cachefile = strtolower(APPKEY) . '_' . $_tplid . '_' . str_replace('/', '_', $file) . '_tpl.php';
    $cachetpl = getini('data/_view') . $cachefile;
    $cachetime = is_file($cachetpl) ? filemtime($cachetpl) : 0;
    checktplrefresh($tplfile, $tplfile, $cachetime, $cachefile, $file);
    ob_get_length() && ob_clean();
    ob_start();
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

/**
 * @param $code
 * @param $data
 */
function dblog($data, $code = 0) {
    $post = [
        'dateline' => time(),
        'logcode' => $code,
        'logmsg' => var_export($data, true)
    ];
    \Rsf\Db::dbm('general')->create('weixin_log', $post);
}