<?PHP

namespace Firelit;

abstract class InitExtendable {
	
	static public function init($type) {
	
		$args = func_get_args();	
		array_shift($args); // Remove the first argument before passing to object constructor
		
		$class = get_called_class() . $type;
		$reflect  = new \ReflectionClass($class);
		return $reflect->newInstanceArgs($args);
		
	}
	
}
