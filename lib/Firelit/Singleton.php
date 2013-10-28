<?php

namespace Firelit;

abstract class Singleton {
	
	static protected $singleton = false;

	static public function init() {

		if (static::$singleton) return static::$singleton;

		$args = func_get_args();
		$class = get_called_class();

		$r = new ReflectionClass($class);
		static::$singleton = $r->newInstanceArgs($args);

		return static::$singleton;

	}

}