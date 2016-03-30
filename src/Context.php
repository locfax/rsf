<?php

namespace Rsf;

class Context {

    const _USERKEY = 'um';
    const _ROLEKEY = 'roles';

    private static $sess_isset = 0;

    /**
     * @param $name 区分大小写
     * @return bool|mixed
     */
    public static function config($name) {
        return Hook::loadFile('config/' . $name . '.inc', false, PSROOT);
    }

    public static function setUser($userData, $rolesData = null, $left = 0) {
        if (!is_null($rolesData)) {
            $userData[self::_ROLEKEY] = $rolesData;
        }
        $data_struct = array(
            self::_USERKEY => $userData
        );
        $ret = self::_set($data_struct, $left);
        return $ret;
    }

    public static function getUser() {
        $datakey = self::_USERKEY;
        $ret = self::_get($datakey);
        if (isset($ret[$datakey])) {
            return $ret[$datakey];
        }
        return null;
    }

    public static function clearUser() {
        $arr = array(
            self::_USERKEY => ''
        );
        self::_set($arr, -86400 * 365);
    }

    public static function getRoles() {
        $user = self::getUser();
        return isset($user[self::_ROLEKEY]) ?
            $user[self::_ROLEKEY] :
            null;
    }

    public static function getRolesArray() {
        $roles = self::getRoles();
        if (empty($roles)) {
            return array();
        }
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        return array_map('trim', $roles);
        //return array_filter($tmp, 'trim');
    }

    public static function get($key, $type = null) {
        //data is string
        if (is_array($key)) {
            if (in_array(self::_USERKEY, $key)) {
                //禁止获取用户信息
                return false;
            }
        } else {
            if (self::_USERKEY == $key) {
                //禁止获取用户信息
                return false;
            }
        }
        $ret = self::_get($key, $type);
        return $ret[$key];
    }

    public static function set(array $data, $left = 0, $type = null) {
        //data is array
        if (isset($data[self::_USERKEY])) {
            //禁止设置用户信息
            return false;
        }
        return self::_set($data, $left, $type);
    }

    public static function clear($key, $type = null) {
        //key is mix
        if (is_array($key)) {
            if (in_array(self::_USERKEY, $key)) {
                //禁止清理用户信息
                return false;
            }
            $arr = array_fill_keys($key, '');
        } else {
            if (self::_USERKEY == $key) {
                //禁止清理用户信息
                return false;
            }
            $arr = array(
                $key => ''
            );
        }
        self::_set($arr, -86400 * 365, $type);
    }

    private static function _get($keys, $type = null) {
        $ret = array();
        if (is_null($type)) {
            $type = getini('auth/handle');
        }
        if ('SESSION' == $type) {
            if (!self::$sess_isset) {
                session_start();
                self::$sess_isset = 1;
            }
            if (is_array($keys)) {
                foreach ($keys as $key) {
                    $ret[$key] = isset($_SESSION[getini('auth/prefix') . $key]) ? $_SESSION[getini('auth/prefix') . $key] : null;
                }
            } else {
                $ret[$keys] = isset($_SESSION[getini('auth/prefix') . $keys]) ? $_SESSION[getini('auth/prefix') . $keys] : null;
            }
        } elseif ('COOKIE' == $type) {
            if (is_array($keys)) {
                foreach ($keys as $key) {
                    $ret[$key] = (null != self::_getcookie($key)) ? json_decode(self::_authcode(self::_getcookie($key), 'DECODE'), true) : null;
                }
            } else {
                $ret[$keys] = (null != self::_getcookie($keys)) ? json_decode(self::_authcode(self::_getcookie($keys), 'DECODE'), true) : null;
            }
        }
        return $ret;
    }

    /*
     * $arr array()
     * $lift int
     * prefix  int 1
     */

    private static function _set($data, $life = 0, $type = null) {
        $ret = false;
        if (is_null($type)) {
            $type = getini('auth/handle');
        }
        if ('SESSION' == $type) {
            if (!self::$sess_isset) {
                //$life && session_set_cookie_params($life);
                session_start();
                self::$sess_isset = 1;
            }
            foreach ($data as $key => $val) {
                if ($life >= 0 && $val) {
                    $_SESSION[getini('auth/prefix') . $key] = $val;
                } else {
                    unset($_SESSION[getini('auth/prefix') . $key]);
                }
            }
            $ret = true;
        } elseif ('COOKIE' == $type) {
            foreach ($data as $key => $val) {
                $val = $val ? self::_authcode(json_encode($val), 'ENCODE') : '';
                self::_setcookie($key, $val, $life);
            }
            $ret = true;
        }
        return $ret;
    }

    //set cookies
    private static function _setcookie($var, $value, $life, $prefix = true, $key = null) {
        static $setp3p = false;
        if ($prefix) {
            if (is_null($key)) {
                $var = getini('auth/prefix') . substr(md5(getini('auth/key')), -7, 7) . '_' . $var;
            } else {
                $var = getini('auth/prefix') . $key . '_' . $var;
            }
        }
        if (false === $setp3p) {
            //header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"'); //跨域
            $setp3p = true;
        }
        $_life = $life > 0 ? (time() + $life) : 0;
        return setcookie($var, $value, $_life, getini('auth/path'), getini('auth/domain'), 443 == getgpc('s.SERVER_PORT') ? 1 : 0);
    }

    private static function _getcookie($var, $prefix = true, $key = null) {
        if ($prefix) {
            if (is_null($key)) {
                $var = getini('auth/prefix') . substr(md5(getini('auth/key')), -7, 7) . '_' . $var;
            } else {
                $var = getini('auth/prefix') . $key . '_' . $var;
            }
        }
        return getgpc('c.' . $var);
    }

    /* string to code
     * return string
     */

    private static function _authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        $timestamp = time();
        $hash_key = getini('auth/key') ? getini('auth/key') : substr(md5(PHP_VERSION), 2, 5);
        $hash_auth = md5($hash_key . PHP_VERSION);
        $ckey_length = 4;
        $_key = md5($key ? $key : $hash_auth);
        $keya = md5(substr($_key, 0, 16));
        $keyb = md5(substr($_key, 16, 16));
        $keyc = $ckey_length ? ('DECODE' == $operation ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $_string = 'DECODE' == $operation ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + $timestamp : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($_string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($_string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ('DECODE' == $operation) {
            if ((0 == substr($result, 0, 10) || substr($result, 0, 10) - $timestamp > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            }
            return '';
        }
        return $keyc . str_replace('=', '', base64_encode($result));
    }

}
