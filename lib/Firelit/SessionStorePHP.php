<?PHP

namespace Firelit;

class SessionStorePHP extends SessionStore {
	
	public function __construct() {
		
		if (headers_sent()) 
			throw new \Exception('Session could not be started because HTTP headers already sent.');
		
		session_start();
		
	}
	
	public function store($valueArray, $expireSeconds = false) {
		// $expireSeconds ignored - session expired handled by php.ini
		
		foreach ($valueArray as $key => $val) {
			if (is_null($val)) 
				unset($valueArray[$key]);
		}
		
		$_SESSION = $valueArray;
		
		return;
		
	}
	
	public function fetch() {
		
		return $_SESSION;
		
	}
	
	public function destroy() {
		
		return session_destroy();
		
	}
	
}