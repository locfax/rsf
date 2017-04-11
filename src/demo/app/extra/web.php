<?php

/**
 * @param $filesite
 * @param $filepath
 * @return string
 */
function file_url($filesite, $filepath) {
    return getini('file/' . $filesite . '/url') . $filepath;
}

/**
 * @param $filesite
 * @param $filepath
 * @return string
 */
function file_path($filesite, $filepath) {
    return getini('file/' . $filesite . '/dir') . $filepath;
}

/**
 * @param $filesite
 * @param $filepath
 * @param string $prefix
 * @return string
 */
function image_url($filesite, $filepath, $prefix = 'source') {
    if ('source' == $prefix) {
        return getini('file/' . $filesite . '/url') . $filepath;
    } else {
        return getini('file/' . $filesite . '/url') . $filepath . '.' . $prefix . '.jpg';
    }
}

/**
 * 头像路径
 * @param $uid
 * @return array
 */
function avatar_path($uid) {
    $uid = sprintf("%09d", abs(intval($uid)));
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    return ['path' => $dir1 . '/' . $dir2 . '/' . $dir3, 'fix' => substr($uid, -2)];
}

function hashids($id, $decode = false) {
    $vhash = \Rsf\Hook::getVendor('hashids/hashids', 'Hashids\Hashids');
    if (!$decode) {
        $ret = $vhash->encode($id);
    } else {
        $rets = $vhash->decode($id);
        $ret = $rets[0];
    }
    return $ret;
}