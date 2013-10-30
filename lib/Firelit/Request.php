<?PHP

namespace Firelit;

class Request extends Singleton {
	
	// All properties accessible via magic getter method
	private $ip, $proxies, $host, $path, $method, $secure, $referer, $protocol, $cli, $headers, $uri;
	private $post, $get, $cookie;
	
	public static $loadBalanced = false;

	// $filter should be a filtering function, if supplied, which filters a string value by reference
	public function __construct($filter = false, $bodyFormat = 'querystring') { 
		
		$this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
		$this->proxies = array();
		$this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
		$this->path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false;
		$this->secure = isset($_SERVER['HTTPS']) ? ($_SERVER['HTTPS'] == 'on') : false;
		$this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
		$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
		$this->protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : false;

		$this->cli = (php_sapi_name() == 'cli');
		if ($this->cli) $this->method = 'CLI';
		
		$this->uri = ($this->cli ? false : ($this->secure ? 'https' : 'http') .'://'. $this->host . $this->path);
		
		if (is_callable('apache_request_headers')) {
			
			$this->headers = apache_request_headers();
			
			if (self::$loadBalanced) {
				
				if (isset($this->headers['X-Forwarded-For'])) {
					$ips = $this->headers['X-Forwarded-For'];
					$ips = explode(', ', $ips);
					$this->ip = array_shift($ips);
					
					$this->proxies = $ips;
				}
				
				if (isset($this->headers['X-Forwarded-Proto'])) {
					$this->secure = ($this->headers['X-Forwarded-Proto'] == 'HTTPS');
				}
				
			} elseif (isset($this->headers['X-Forwarded-For'])) {
				
				$ips = $this->headers['X-Forwarded-For'];
				$ips = explode(', ', $ips);
				$this->proxies = $ips;
				
			}
		
		} else
			$this->headers = array();
			
		if ($bodyFormat == 'json') {
			
			$stream = fopen('php://input', 'r');
			$this->post = stream_get_contents($stream);
			fclose($stream);

			$this->post = json_decode($this->post, true);

		} else {
			$this->post = $_POST;
		}

		$this->get = $_GET;
		$this->cookie = $_COOKIE;
		
		if ($filter) {
			// Filter local copies of POST, GET & COOKIE data
			// Unset global versions to prevent access to un-filtered
			$this->filterInputs($filter);
			
			$_POST = null;
			$_GET = null;
			$_COOKIE = null;
			
		}

	}
	
	public function filterInputs($filter = false) {
		
		if ($filter == false) return;
		if (!is_callable($filter)) 
			throw new \Exception('Specified filter is not callable.');
		
		$this->recurse($this->post, $filter);
		$this->recurse($this->get, $filter);
		$this->recurse($this->cookie, $filter);
	
	}
	
	protected function recurse(&$input, &$function) {
	
		if (is_array($input))
			foreach ($input as $name => &$value)
				$this->recurse($value, $function);
		else
			$function($input);
			
	}
	
	public function __get($name) {
	
		if (isset($this->$name)) return $this->$name;
		
		throw new \Exception('Invalid property specified.');
		
	}

}