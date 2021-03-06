<?php

namespace Firelit;

class Router extends Singleton
{

    protected $method;
    protected $uri;
    protected $match = false;
    protected $default;
    protected $error = array();
    protected $exceptionHandler = false;
    protected $testMode = false;
    protected $routes = array();

    public $request;
    public $response;
    public $parameters = array();

    public static $proto = 'http';
    public static $domain = 'localhost';
    public static $rootPath = '/';
    public static $catchExceptions = false;

    public function __construct(Request $request = null)
    {

        // Check registry for router, if not passed
        if (is_null($request)) {
            $request = Registry::get('Router');
        }

        $this->request = $request;

        $this->method = $request->method;

        $rootPath = self::$rootPath;
        if (preg_match('!/$!', $rootPath)) {
            $rootPath = substr($rootPath, 0, -1);
        }

        $this->uri = preg_replace('!^'. preg_quote($rootPath) .'!', '', $request->path);
        if (strpos($this->uri, '?')) {
            $this->uri = substr($this->uri, 0, strpos($this->uri, '?'));
        }
    }

    public function __destruct()
    {

        if ($this->match || !is_callable($this->default)) {
            return; // Response already sent or no default set
        }
        if (in_array(http_response_code(), array(301, 302, 303))) {
            return; // No default route if redirect
        }

        try {
            $this->default();
        } catch (RouteToError $e) {
            $this->triggerError($e->getCode(), $e->getMessage());
        }

        exit;
    }

    /**
     * Magic method used specifically for calling the default route and exception handler
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (isset($this->$method) && is_callable($this->$method)) {
            return call_user_func_array($this->$method, $args);
        }
    }

    /**
     * Check the method and uri and run the supplied function if match.
     * The $execute function is passed an array of matches from $regExpUrlMatch
     *
     * @param  int|array $filterMethod
     * @param  string|bool $regExpUrlMatch
     * @param  function $execute
     * @return void
     */
    public function add($filterMethod, $regExpUrlMatch, $execute)
    {

        if (!is_array($filterMethod)) {
            $filterMethod = array($filterMethod);
        }

        if (!is_callable($execute) && !$execute instanceof Router) {
            throw new \Exception('The last parameter to Router::add must be a callable function or an instance of the Firelit\Router class');
        }

        $this->routes[] = array(
            'method' => $filterMethod,
            'matcher' => $regExpUrlMatch,
            'function' => $execute
        );
    }

    public function go($uri = false)
    {

        if (empty($uri)) {
            $uri = $this->uri;
        }

        foreach ($this->routes as $route) {
            $filterMethod = $route['method'];
            $regExpUrlMatch = $route['matcher'];
            $execute = $route['function'];

            // (1) Does the request method match?
            if (!in_array('*', $filterMethod) && !in_array($this->method, $filterMethod)) {
                continue;
            }

            $params = array();

            // (2) Does the URI match? (set $regExpUrlMatch to false to skip)
            if ($regExpUrlMatch && ($this->method == 'CLI')) {
                continue;
            }
            if ($regExpUrlMatch && !preg_match($regExpUrlMatch, $uri, $params)) {
                continue;
            }

            // Method and URI match!
            $this->match = true;

            // Remove the full text match from the match array
            array_shift($params);

            try {
                // Go!
                if ($execute instanceof Router) {
                    $subUri = preg_replace($regExpUrlMatch, '', $uri);
                    $execute->go($subUri);
                } else {
                    $execute($params);
                }
            } catch (RouteToError $e) {
                $this->triggerError($e->getCode(), $e->getMessage());
                // If not exited, throw it back up (could be caught by another nested route)
                throw $e;
            }

            // We had a match: No more checking routes!
            return;
        }
    }

    /**
     * Set an error route for a specific error code (or 0 for default/catch-all)
     * Function to execute will be passed two parameters: the error code & an optional error message
     *
     * @param  int|array $errorCode
     * @param  function $execute
     * @return Firelit\Router
     */
    public function errorRoute($errorCode, $execute)
    {
        if (!is_array($errorCode)) {
            $errorCode = array($errorCode);
        }

        foreach ($errorCode as $thisCode) {
            $this->error[$thisCode] = $execute;
        }

        return $this;
    }

    /**
     * Set the default route if no other routes match
     *
     * @param  function $execute
     * @return Firelit\Router
     */
    public function defaultRoute($execute)
    {
        $this->default = $execute;

        return $this;
    }

    /**
     * A function to handle any exceptions that are caught
     * The passed function should take one parameter, an exception object
     *
     * @param  function $execute
     * @return Firelit\Router
     */
    public function exceptionHandler($execute)
    {
        $this->exceptionHandler = $execute;
        set_exception_handler($this->exceptionHandler);

        return $this;
    }

    /**
     * Trigger an error route based on the error code and exit script with that error code
     *
     * @param  int $errorCode
     * @param  string $errorMessage
     * @return void
     */
    public function triggerError($errorCode, $errorMessage = '')
    {
        $callError = $errorCode;

        if (!isset($this->error[$errorCode]) || !is_callable($this->error[$errorCode])) {
            if (isset($this->error[0]) && is_callable($this->error[0])) {
                $callError = 0;
            } else {
                // Nothing set to handle this error!
                return;
            }
        }

        // Error response function exists
        $this->match = true;

        //call_user_func_array($this->error[$errorCode], array($errorCode, $errorMessage));
        $this->error[$callError]($errorCode, $errorMessage);
        // Or use call_user_func_array ?

        exit($errorCode);
    }
}
