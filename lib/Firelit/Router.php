<?php

namespace Firelit;

class Router {
	
	protected $method, $uri;
	
	public $request, $response, $parameters = array();
	
	public static $proto = 'http', $domain = 'localhost', $rootPath = '/';
	
	public function __construct(Firelit\ServerRequest $request) {
	
		$this->request = $request;
		
		$this->method = $request->method;
		
		$rootPath = self::$rootPath;
		if (preg_match('!/$!', $rootPath)) $rootPath = substr($rootPath, 0, -1);
		
		$this->uri = preg_replace('!^'. preg_quote($rootPath) .'!', '', $request->path);
		if (strpos($this->uri, '?')) $this->uri = substr($this->uri, 0, strpos($this->uri, '?'));
		
	}
	
	/**
	 * Check the method and uri and run the supplied function if match.
	 *
	 * @param  string $filterMethod
	 * @param  string $regExpUrlMatch
	 * @param  function $execute
	 * @return void
	 */
	public function add($filterMethod, $regExpUrlMatch, $execute) {
		
		$filterMethods = explode(',', strtoupper($filterMethod));
		
		// (1) Does the request method match?
		if (!in_array('*', $filterMethods) && !in_array($this->method, $filterMethods)) return;
		
		$params = array();
		
		// (2) Does the URI match?
		if (!preg_match($regExpUrlMatch, $this->uri, $params)) return;
		
		// Method and URI match!
		
		// Remove the full text match from the match array
		array_shift($params);
		
		// Go!
		$execute($params);
		
		// End execution
		exit;
		
	}
	
}
