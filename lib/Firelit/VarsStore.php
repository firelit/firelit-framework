<?php

namespace('Firelit');

abstract class VarsStore extends InitExtendable {
	
	abstract public function set($name, $value);
	
	abstract public function get($name);
	
}