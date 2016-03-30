<?php

namespace Rsf\Cache;

class File {

    use \Rsf\Base\Singleton;

    public $enable = false;

    public function init() {
        if (is_dir(getini('data/_cache'))) {
            $this->enable = true;
        } else {
            throw new \Rsf\Exception\Exception('路径:' . getini('data/_cache') . ' 不可写');
        }
        return $this;
    }

    public function get($key) {
        $cachefile = getini('data/_cache') . $key . '.php';
        if (is_file($cachefile)) {
            $data = include $cachefile;
            if ($data && $data['timeout'] > time()) {
                return $data['data'];
            }
            unlink($cachefile);
        }
        return null;
    }

    public function set($key, $val, $ttl = 0) {
        if ($ttl > 0) {
            $timeout = time() + $ttl;
        } else {
            //默认存储一个月
            $timeout = time() + 30 * 24 * 3600;
        }

        $cachefile = getini('data/_cache') . $key . '.php';
        $cachedata = "return array('data' => '{$val}', 'timeout' => {$timeout});";
        $content = "<?php \n//CACHE FILE, DO NOT MODIFY ME PLEASE!\n//Identify: " . md5($key . time()) . "\n\n{$cachedata}";
        return $this->save($cachefile, $content, FILE_WRITE_MODE);
    }

    public function rm($key) {
        $cachefile = getini('data/_cache') . $key . '.php';
        if (file_exists($cachefile)) {
            unlink($cachefile);
        }
        return true;
    }

    public function clear() {
        $cachedir = getini('data/_cache');
        $files = \helper\file::getInstance()->list_files($cachedir);
        foreach ($files as $file) {
            unlink($cachedir . $file);
        }
        return true;
    }

    public function save($filename, $content, $mode) {
        if (!is_file($filename)) {
            file_exists($filename) && unlink($filename);
            touch($filename) && chmod($filename, FILE_WRITE_MODE); //全读写
        }
        $ret = file_put_contents($filename, $content, LOCK_EX);
        if ($ret && FILE_WRITE_MODE != $mode) {
            chmod($filename, $mode);
        }
        return $ret;
    }

}