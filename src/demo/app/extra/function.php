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
 * @param $filesite
 * @param $filepath
 * @return string
 */
function file_path($filesite, $filepath) {
    return getini('file/' . $filesite . '/dir') . $filepath;
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

/**
 * 解密函数
 *
 * @param string $txt
 * @param string $key
 * @return string
 */
function passport_decrypt($txt, $key) {
    $txt = passport_key(base64_decode($txt), $key);
    $tmp = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $md5 = $txt[$i];
        $tmp .= $txt[++$i] ^ $md5;
    }
    return $tmp;
}

/**
 * 加密函数
 *
 * @param string $txt
 * @param string $key
 * @return string
 */
function passport_encrypt($txt, $key) {
    srand((double)microtime() * 1000000);
    $encrypt_key = md5(rand(0, 32000));
    $ctr = 0;
    $tmp = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
        $tmp .= $encrypt_key[$ctr] . ($txt[$i] ^ $encrypt_key[$ctr++]);
    }
    return base64_encode(passport_key($tmp, $key));
}

/**
 * 编码函数
 * @param $txt
 * @param $encrypt_key
 * @return string
 */
function passport_key($txt, $encrypt_key) {
    $encrypt_key = md5($encrypt_key);
    $ctr = 0;
    $tmp = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
        $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
    }
    return $tmp;
}