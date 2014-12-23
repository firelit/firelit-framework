<?PHP

namespace Firelit;

class HttpRequest {
	
	// cURL handle
	private $handle = false;
	
	// Cookies
	private $cookies = false;
	public $cookieFile = false; // File where the cookie data is stored
	public $delCookieFile = true; // Delete when object destructs
	
	// If set, overwrites $config
	public $userAgent = false;
	
	private $respCode = 0;
	
	public static $config = array(
		'timeout' => array(
			'connect' => 3,
			'response' => 10
		),
		'userAgent' => false,
		'caInfo' => false
	);
	
	public function __construct() {
		
		if (function_exists('curl_init')) $this->handle = curl_init();
		else throw new Exception('cURL required.');
		
		if (!$this->handle) throw new Exception('Could not initiate cURL.');
		
	}
	
	public static function config($config) {
		
		self::$config = array_merge(self::$config, $config);
		
	}
	
	public function enableCookies($file = false, $delOnDestruct = true) {
		
		$this->cookies = true;
		
		if ($file && strlen($file)) 
			$this->cookieFile = $file;
		else 
			$this->cookieFile = tempnam(".", "CURL-COOKIE-");
			
		$this->delCookieFile = $delOnDestruct;
		
	}
	
	public function close() {
		
		if (!$this->handle) return;
		
		curl_close($this->handle);
		
		$this->handle = false;
		
	}
	
	public function clearCookies() {
		
		if ($this->cookies && $this->delCookieFile && file_exists($this->cookieFile))
			unlink($this->cookieFile);
		
	}
	
	public function customHeaders($headerArray) {
		
		curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headerArray);
		
	}

	public function setBasicAuth($user, $pass = null) {

		curl_setopt($this->handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC) ; 
		curl_setopt($this->handle, CURLOPT_USERPWD, (!empty($pass) ? $user .':'. $pass : $user)); // Should be 'username:password'

	}
	
	// Three executing methods:
	
	public function get($url) {
		
		curl_setopt($this->handle, CURLOPT_POST, 0); // Added to clear past values
		curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'GET'); // Added to clear past values
		
		return $this->execute($url);
		
	}
	
	public function post($url, $postData) {
	
		if (is_array($postData)) $postData = http_build_query($postData);
		
		curl_setopt($this->handle, CURLOPT_POST, 1); // Perform a POST
		curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'POST'); // Added to clear past values
		curl_setopt($this->handle, CURLOPT_POSTFIELDS, $postData);
		
		return $this->execute($url);
		
	}
	
	public function put($url, $putData) {
	
		if (is_array($putData)) $putData = http_build_query($putData);
		
		curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($this->handle, CURLOPT_POSTFIELDS, $putData);
		
		return $this->execute($url);
		
	}
	
	public function other($url, $type) {
		// For DELETE, PUT, HEAD, etc
		curl_setopt($this->handle, CURLOPT_POST, 0); // Added to clear past values
		curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $type);
		
		return $this->execute($url);
		
	}
	
	private function execute($url) {
			
		curl_setopt($this->handle, CURLOPT_URL, $url); // Set the URL
		
		if ($this->userAgent) curl_setopt($this->handle, CURLOPT_USERAGENT, $this->userAgent);
		elseif (self::$config['userAgent']) curl_setopt($this->handle, CURLOPT_USERAGENT, self::$config['userAgent']);
		
		if (self::$config['caInfo']) curl_setopt($this->handle, CURLOPT_CAINFO, self::$config['caInfo']); // Name of the file to verify the server's cert against
		if (self::$config['caInfo']) curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, 1); // Turns on verification of the SSL certificate.
		
		curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1); // If not set, curl prints output to the browser
		curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, self::$config['timeout']['connect']); // How long to wait for a connection
		curl_setopt($this->handle, CURLOPT_TIMEOUT, self::$config['timeout']['response']); // How long to wait for a response
		
		if ($this->cookies) {
			curl_setopt($this->handle, CURLOPT_COOKIEJAR, $this->cookieFile); 
			curl_setopt($this->handle, CURLOPT_COOKIEFILE, $this->cookieFile); 
		}
		
		$dataBack = curl_exec($this->handle);
		
		$this->respCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
		
		return $dataBack;
		
	}
	
	public function respCode() {
		
		return $this->respCode;
		
	}
	
	public function __destruct() {
		
		$this->close();
		$this->clearCookies();
		
	}
	
}