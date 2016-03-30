<?php

namespace Rsf;

class App {


    /**
     * @param array $preload
     * @param bool $refresh
     */
    public static function run($preload, $refresh = false) {
        if (!defined('APPKEY')) {
            exit('APPKEY not defined!');
        }
        $dfiles = array(
            BASE . 'dispatch.php', //解析器
            BASE . 'controller.php', //控制器
        );
        $preload = array_merge($dfiles, $preload);
        self::runFile($preload, $refresh);
        Dispatch::dispatching();
    }

    /**
     * @param $preload
     * @param bool $refresh
     */
    public static function runFile($preload, $refresh = false) {
        $preloadfile = GDATA . 'preload/runtime_' . APPKEY . '_files.php';
        if (!is_file($preloadfile) || $refresh) {
            $dfiles = array(
                LIBS . 'config/base.inc.php', //全局配置
                LIBS . 'config/' . APPKEY . '.dsn.php', //数据库配置
                LIBS . 'config/' . APPKEY . '.inc.php', //应用配置
                BASE . 'common.php', //通用函数
                BASE . 'utils.php', //功能函数
                BASE . 'dblink.php', //DB
            );
            $files = array_merge($dfiles, $preload);
            $preloadfile = self::makeRunFile($files, $preloadfile);
        }
        $preloadfile && require $preloadfile;
    }

    /**
     * @param $runtimefiles
     * @param $runfile
     * @return bool
     */
    public static function makeRunFile($runtimefiles, $runfile) {
        $content = '';
        foreach ($runtimefiles as $filename) {
            $data = php_strip_whitespace($filename);
            $content .= str_replace(array('<?php', '?>', '<php_', '_php>'), array('', '', '<?php', '?>'), $data);
        }
        if (!is_file($runfile)) {
            file_exists($runfile) && unlink($runfile); //可能是异常文件 删除
            touch($runfile) && chmod($runfile, 0777); //生成全读写空文件
        } elseif (!is_writable($runfile)) {
            chmod($runfile, 0777); //全读写
        }
        $ret = file_put_contents($runfile, '<?php ' . $content, LOCK_EX);
        if ($ret) {
            //chmod($runfile, 0644); //全只读
            return $runfile;
        }
        return false;
    }

    /**
     * @param $group
     * @param null $vars
     * @return mixed
     */
    public static function mergeVars($group, $vars = null) {
        static $_CDATA = array('dsn' => null, 'cfg' => null, 'data' => array()); //内部变量缓冲
        if (is_null($vars)) {
            return $_CDATA[$group];
        } else {
            if (is_null($_CDATA[$group])) {
                $_CDATA[$group] = $vars;
            } else {
                $_CDATA[$group] = array_merge($_CDATA[$group], $vars);
            }
        }
    }

    /**
     * @param $code
     * @param $data
     */
    public static function log($code, $data) {
        $post = array(
            'dateline' => time(),
            'logcode' => $code,
            'logmsg' => var_export($data, true)
        );
        DB::get('general')->create('weixin_log', $post);
    }

    /**
     * @param bool $retbool
     * @return bool
     */
    public static function isPost($retbool = true) {
        if ('POST' == getgpc('s.REQUEST_METHOD')) {
            return $retbool;
        }
        return !$retbool;
    }

    /**
     * @param bool $retbool
     * @return bool
     */
    public static function isAjax($retbool = true) {
        if ('XMLHttpRequest' == getgpc('s.HTTP_X_REQUESTED_WITH')) {
            return $retbool;
        }
        return !$retbool;
    }
}