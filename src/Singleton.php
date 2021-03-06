<?php

namespace Firelit;

abstract class Singleton
{
    
    static protected $singletons = array();

    public static function init()
    {

        $class = get_called_class();

        if (!isset(self::$singletons[$class])) {
            $args = func_get_args();
            $r = new \ReflectionClass($class);
            self::$singletons[$class] = $r->newInstanceArgs($args);
        }

        return self::$singletons[$class];
    }

    public static function destruct()
    {

        $class = get_called_class();
        if (isset(self::$singletons[$class])) {
            unset(self::$singletons[$class]);
        }
    }
}
