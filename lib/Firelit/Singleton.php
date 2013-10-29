<?php

namespace Firelit;

abstract class Singleton {
	
	static protected $singletons = array();

	static public function init() {

		$class = get_called_class();

		if (!isset(static::$singletons[$class])) {

			$args = func_get_args();
			$r = new \ReflectionClass($class);
			static::$singletons[$class] = $r->newInstanceArgs($args);

		}

		return static::$singletons[$class];

	}

}