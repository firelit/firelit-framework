<?PHP

namespace Firelit;

abstract class Controller {
	
	static public $request, $response, $router, $authenticator;
	static public $debug = false;
	static public $page = 0, $pagesize = 50, $url;
	
	abstract public function __construct();
	
	static public function setup(Firelit\Request $request, Firelit\Response $response, Router $router = null, Authenticator $authenticator = null) {
	
		self::$request = $request;
		self::$response = $response;
		self::$router = $router;
		self::$authenticator = $authenticator;

		if (isset( self::$request->get['page'] )) {
			self::$page = intval( self::$request->get['page'] );
			if (self::$page < 0) self::$page = 0;
		}

		if (isset( self::$request->get['pagesize'] )) {
			self::$pagesize = intval( self::$request->get['pagesize'] );
			if (self::$pagesize < 10) self::$pagesize = 10;
			if (self::$pagesize > 250) self::$pagesize = 250;
		}
		
	}
	
	static public function handoff($resource, $action = '') {
		
		try {

			$controller = $resource . __class__ . $action;
			
			$args = func_get_args();	
			
			if (sizeof($args) <= 2) 
				return new $controller();
			
			// If parameters passed, be sure to pass them on:
			array_shift($args);
			array_shift($args);
			
			$reflect  = new \ReflectionClass($controller);
			return $reflect->newInstanceArgs($args);
			
		} catch (ControllerError $e) {

			self::$response->code( $e->getCode() );
			self::$response->set( array('error' => $e->getMessage()) );

			if (self::$debug) {

				$debugArray = array(
					'file' => $e->getFile(),
					'line' => $e->getLine(),
					'trace' => $e->getTraceAsString()
				);

				$prev = $e->getPrevious();
				if (is_object($prev))
					$debugArray['previous'] = $prev->getMessage();

				self::$response->set( array('debug' => $debugArray) );
			
			}
			
			self::$response->respond();

			exit;

		}

	}

}