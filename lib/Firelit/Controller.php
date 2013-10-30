<?PHP

namespace Firelit;

abstract class Controller {
	
	abstract public function __construct();
	
	/**
	 * Handoff control to a/another controller
	 *
	 * @param  string $controller
	 * @param  string|bool $method
	 * @return mixed
	 */
	static public function handoff($controller, $method = false) {
	
		$args = func_get_args();	
		
		// If parameters passed, be sure to pass them on:
		array_shift($args);
		if (sizeof($args)) array_shift($args);
		
		// Create a new instance of the class
		$reflect = new \ReflectionClass($controller);
		$newClass = $reflect->newInstanceArgs($method ? array() : $args);
		
		if ($method) {
			// Execute a method
			$reflectMethod = new \ReflectionMethod($controller, $method);
			return $reflectMethod->invokeArgs($newClass, $args);
		}

		// If method specified, return the result of that method call (above)
		// Else return the object itself
		return $newClass;
		
	}

}