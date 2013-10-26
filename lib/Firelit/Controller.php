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
		if (sizeof($args)) array_shift($args);
		if (sizeof($args)) array_shift($args);
		
		$reflect = new \ReflectionClass($controller);
		$return = $reflect->newInstanceArgs($args);

		// If method specified, return the result of that method call
		if ($method) return $return->$method;
		// Else return the object itself
		else return $return;
		
	}

}