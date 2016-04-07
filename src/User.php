<?php

namespace Rsf;

class User {

    const _USERKEY = 'um';
    const _ROLEKEY = 'roles';

    private static $sess_isset = 0;


    /**
     * @param $userData
     * @param string $uid
     * @param null $rolesData
     * @param int $left
     * @return bool
     */
    public static function setUser(array $userData, $uid = '', $rolesData = null, $left = 0) {
        if (!is_null($rolesData)) {
            $userData[self::_ROLEKEY] = $rolesData;
        }
        $data_struct = [
            self::_USERKEY . $uid => $userData
        ];
        $ret = self::_setdata($data_struct, $left);
        return $ret;
    }

    /**
     * @param string $uid
     * @return null
     */
    public static function getUser($uid = '') {
        $datakey = self::_USERKEY . $uid;
        $ret = self::_getdata($datakey);
        if (isset($ret[$datakey])) {
            return $ret[$datakey];
        }
        return null;
    }

    public static function clearUser($uid = '') {
        $arr = [
            self::_USERKEY . $uid => ''
        ];
        self::_setdata($arr, -86400 * 365);
    }

    /**
     * @param string $uid
     * @return null
     */
    public static function getRoles($uid = '') {
        $user = self::getUser($uid);
        return isset($user[self::_ROLEKEY]) ?
            $user[self::_ROLEKEY] :
            null;
    }


    /**
     * @param string $uid
     * @return array
     */
    public static function getRolesArray($uid = '') {
        $roles = self::getRoles($uid);
        if (empty($roles)) {
            return [];
        }
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        return array_map('trim', $roles);
    }

    /**
     * @param $keys
     * @param null $type
     * @return array
     */
    private static function _getdata($key, $type = null) {
        $ret = '';
        if (is_null($type)) {
            $type = getini('auth/handle');
        }
        if ('SESSION' == $type) {
            if (!self::$sess_isset) {
                session_start();
                self::$sess_isset = 1;
            }
            $ret = isset($_SESSION[getini('auth/prefix') . $key]) ? $_SESSION[getini('auth/prefix') . $key] : null;
        } elseif ('COOKIE' == $type) {
            $ret = (null != self::_getcookie($key)) ? json_decode(self::_authcode(self::_getcookie($key), 'DECODE'), true) : null;
        } elseif ('REDIS' == $type) {
            $redis = Db::dbo('redis');
            $data = $redis->get($key);
            $ret = $data ? $data : null;
        }
        return $ret;
    }

    /*
     * $arr array()
     * $lift int
     * prefix  int 1
     */

    private static function _setdata($data, $life = 0, $type = null) {
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
        } elseif ('REDIS' == $type) {
            $redis = Db::dbo('redis.user');
            foreach ($data as $key => $val) {
                $redis->set($key, $val, $life);
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
        return self::$response->withCookie($var, $value, $_life, getini('auth/path'), getini('auth/domain'), 443 == self::$request->getServerParam('SERVER_PORT') ? 1 : 0);
    }

    private static function _getcookie($var, $prefix = true, $key = null) {
        if ($prefix) {
            if (is_null($key)) {
                $var = getini('auth/prefix') . substr(md5(getini('auth/key')), -7, 7) . '_' . $var;
            } else {
                $var = getini('auth/prefix') . $key . '_' . $var;
            }
        }
        return self::$request->getCookieParam($var);
    }

    /* string to code
    * return string
    */

    public static function _authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        static $hash_auth = null;
        if (is_null($hash_auth)) {
            $hash_key = getini('auth/key') ?: PHP_VERSION;
            $hash_auth = md5($hash_key . PHP_VERSION);
        }
        $timestamp = time();
        $ckey_length = 4;
        $_key = md5($key ?: $hash_auth);
        $keya = md5(substr($_key, 0, 16));
        $keyb = md5(substr($_key, 16, 16));
        $keyc = $ckey_length ? ('DECODE' == $operation ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $_string = 'DECODE' == $operation ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + $timestamp : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($_string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
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