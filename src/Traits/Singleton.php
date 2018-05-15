<?php

namespace Rsf\Traits;

trait Singleton
{

    protected static $instances = [];

    public function __clone()
    {
        throw new \Exception('Cloning ' . __CLASS__ . ' is not allowed');
    }

    public static function getInstance($param = null)
    {
        $class = get_called_class();
        if (!isset(static::$instances[$class])) {
            static::$instances[$class] = new static($param);
        }
        return static::$instances[$class];
    }

    public static function clearInstance()
    {
        $class = get_called_class();
        unset(static::$instances[$class]);
    }
}
