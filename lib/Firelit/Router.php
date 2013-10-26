<?php

namespace Firelit;

class Router {
	
	protected $method, $uri, $match = false, $default, $error = array();
	protected $testMode = false;

	public $request, $response, $parameters = array();
	
	public static $proto = 'http', $domain = 'localhost', $rootPath = '/';
	
	public function __construct(Request $request = null) {

		// Check registry for router, if not passed
		if (is_null($request)) $request = Registry::get('Router');
	
		$this->request = $request;
		
		$this->method = $request->method;
		
		$rootPath = self::$rootPath;
		if (preg_match('!/$!', $rootPath)) $rootPath = substr($rootPath, 0, -1);
		
		$this->uri = preg_replace('!^'. preg_quote($rootPath) .'!', '', $request->path);
		if (strpos($this->uri, '?')) $this->uri = substr($this->uri, 0, strpos($this->uri, '?'));
		
		// Set in registry
		Registry::set('Router', $this);

	}
	
	public function __destruct() {

		if ($this->match || !is_callable($this->default)) return;
		
		try {

			$this->default();

		} catch (RouteToError $e) {

			$this->triggerError($e->getCode(), $e->getMessage());

		}

		exit;
	}

	public function __call($method, $args) {
		if (isset($this->$method) && is_callable($this->$method)) {
			return call_user_func_array($this->$method, $args);
		}
	}

	/**
	 * Check the method and uri and run the supplied function if match.
	 *
	 * @param  mixed $filterMethod
	 * @param  mixed $regExpUrlMatch
	 * @param  function $execute
	 * @return void
	 */
	public function add($filterMethod, $regExpUrlMatch, $execute) {
		
		if (!is_array($filterMethod)) $filterMethod = array($filterMethod);
		
		// (1) Does the request method match?
		if (!in_array('*', $filterMethod) && !in_array($this->method, $filterMethod)) return;
		
		$params = array();
		 
		// (2) Does the URI match? (set $regExpUrlMatch to false to skip)
		if ($regExpUrlMatch && ($this->method == 'CLI')) return;
		if ($regExpUrlMatch && !preg_match($regExpUrlMatch, $this->uri, $params)) return;
		
		// Method and URI match!
		
		// Remove the full text match from the match array
		array_shift($params);
		
		try {

			// Go!
			$execute($params);

		} catch (RouteToError $e) {
			
			$this->triggerError($e->getCode(), $e->getMessage());

		}
		
		$this->match = true;

		// End execution
		exit;
		
	}

	public function errorRoute($errorCode, $execute) {
		if (!is_array($errorCode)) $errorCode = array($errorCode);
		foreach ($errorCode as $thisCode) $this->error[$thisCode] = $execute;

		return $this;
	}

	public function defaultRoute($execute) {
		$this->default = $execute;

		return $this;
	}

	public function triggerError($errorCode, $errorMessage = '') {
		if (!isset($this->error[$errorCode]) || !is_callable($this->error[$errorCode])) exit($errorCode);

		//call_user_func_array($this->error[$errorCode], array($errorCode, $errorMessage));
		$this->error[$errorCode]($errorCode, $errorMessage);
		// Or use call_user_func_array ?

		$this->match = true;

		exit($errorCode);
	}
	
}
