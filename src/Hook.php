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
        return $absPath .'/'. $fileName . '.' . $ext;
    }

    /**
     * @param $classPath
     * @return mixed
     */
    private static function _className($classPath) {
        $cn = str_replace('/', "\\", $classPath);
        return ucfirst($cn);
    }

    /**
     * @param $filename 区分大小写
     * @param bool $loadOnce
     * @param string $abspath
     * @param string $ext
     * @return bool|mixed
     */
    public static function loadFile($filename, $loadOnce = true, $abspath = BASE, $ext = 'php') {
        static $is_loaded = array();
        $file = self::_filePath($filename, $abspath, $ext);
        $filemd5 = md5($file);
        if (isset($is_loaded[$filemd5]) && $loadOnce) {
            return true;
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
        if (!include $file) {
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
        static $instances = array();
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
        if (get_class($obj) === $className) {
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
            return self::loadClass('Helper/' . $name, "\\Rsf\Helper\\{$name}");
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
            return self::loadClass('Plugin/' . $classPath , "\\Plugin\\" . self::_className($classPath), PSROOT);
        }
        return self::loadFile('Plugin/' . $classPath, true, PSROOT);
    }

    /**
     * @param $classPath 区分大小写
     * @param null $className 区分大小写
     * @param mixed $classParam
     * @param string $ext
     * @return mixed
     */
    public static function getVendor($classPath, $className = null, $classParam = null, $ext = 'php') {
        if (is_null($className)) {
            //无指定类名 使用标准类名
            $className = '\\vendor\\' . self::_className($classPath);
        }
        return self::getClass('vendor/' . $classPath, $className, $classParam, BASE, $ext);
    }

    /**
     * @param $classPath 区分大小写
     * @param null $className 区分大小写
     * @param string $ext
     * @return bool|mixed|null
     */
    public static function loadVendor($classPath, $className = null, $ext = 'php') {
        if (!is_null($className)) {
            //指定了类名 使用类名加载
            return self::loadClass('vendor/' . $classPath, $className, BASE, $ext);
        }
        return self::loadFile('vendor/' . $classPath, true, BASE, $ext);
    }

}
