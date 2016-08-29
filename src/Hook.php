<?php

namespace Rsf;

class Hook {

    /**
     * @param $fileName
     * @param string $absPath
     * @param string $ext
     * @return bool|string
     */
    private static function _filePath($fileName, $absPath = BASE, $ext = 'php') {
        return $absPath . '/' . $fileName . '.' . $ext;
    }

    /**
     * @param $classPath
     * @return mixed
     */
    private static function _className($classPath) {
        return str_replace('/', "\\", $classPath);
    }

    /**
     * @param $filename 区分大小写
     * @param bool $loadOnce
     * @param string $abspath
     * @param string $ext
     * @return bool|mixed
     */
    public static function loadFile($fileName, $loadOnce = true, $absPath = BASE, $ext = 'php') {
        static $is_loaded = [];
        $file = self::_filePath($fileName, $absPath, $ext);
        $filemd5 = md5($file);
        if (isset($is_loaded[$filemd5]) && $loadOnce) {
            return true;
        }
        if (!is_file($file)) {
            return false;
        }
        $is_loaded[$filemd5] = true;
        return include $file;
    }

    /**
     * @param $classPath 区分大小写
     * @param null $className
     * @param string $absPath
     * @param string $ext
     * @return mixed|null
     * @throws Exception
     */
    public static function loadClass($classPath, $className = null, $absPath = BASE, $ext = 'php') {
        if (is_null($className)) {
            $className = self::_className($classPath);
        }
        if (class_exists($className, false) || interface_exists($className, false)) {
            return true;
        };
        $file = self::_filePath($classPath, $absPath, $ext);
        if (!is_file($file) || !include $file) {
            return false;
        }
        if (class_exists($className, false) || interface_exists($className, false)) {
            return true;
        }
        return false;
    }

    /**
     * @param $classPath 区分大小写
     * @param null $className 区分大小写
     * @param mixed $classParam
     * @param string $absPath
     * @param string $ext
     * @return mixed
     * @throws Exception
     */
    public static function getClass($classPath, $className = null, $classParam = null, $absPath = BASE, $ext = 'php') {
        static $instances = [];
        if (is_null($className)) {
            $className = self::_className($classPath);
        }
        if (isset($instances[$className])) {
            return $instances[$className];
        }
        if (!self::loadClass($classPath, $className, $absPath, $ext)) {
            return false;
        }
        $obj = new $className($classParam);
        if ($obj instanceof className) {
            $instances[$className] = $obj;
            return $instances[$className];
        }
        return false;
    }

    /**
     * 加载助手
     * @param $name 区分大小写
     * @param bool $isClass
     * @return bool|mixed|null
     */
    public static function helper($name, $isClass = false) {
        if ($isClass) {
            return self::loadClass('Helper/' . $name, "\\Rsf\\Helper\\{$name}");
        }
        return self::loadFile('Helper/' . $name);
    }

    /**
     * 加载model
     * @param $classPath 区分大小写
     * @param bool $isClass
     * @return bool|mixed|null
     */
    public static function model($classPath, $isClass = true) {
        if ($isClass) {
            return self::loadClass('Model/' . $classPath, '\\Model\\' . self::_className($classPath), PSROOT);
        }
        return self::loadFile('Model/' . $classPath, true, PSROOT);
    }

    /**
     * 加载插件
     * @param $classPath 区分大小写
     * @param bool $isClass
     * @return bool|mixed|null
     */
    public static function plugin($classPath, $isClass = false) {

        if ($isClass) {
            return self::loadClass('Plugin/' . $classPath, "\\Plugin\\" . self::_className($classPath), PSROOT);
        }
        return self::loadFile('Plugin/' . $classPath, true, PSROOT);
    }

}
