<?PHP

namespace Firelit;

class Session extends Singleton {

	public static $config = array(
		'cookie' => array(
			'name' => 'session',
			'lifetime' => 0, // Expires when browser closes
			'path' => '/',
			'domain' => false, // Set to false to use the current domain
			'secureOnly' => false,
			'httpOnly' => true
		),
		'validatorSalt' => 'dJa832lwkdP1' // Recommend changing to slow session key brute-force spoofing
	);

	public function __construct(\SessionHandlerInterface $store = null, $sessionId = false) {	
		// Create a session using the given SessionHandlerInterface object
		// If null, will use PHP's native SESSION engine

		if ($store) session_set_save_handler($store, true);

		session_set_cookie_params(
			self::$config['cookie']['lifetime'], 
			self::$config['cookie']['path'], 
			self::$config['cookie']['domain'], 
			self::$config['cookie']['secureOnly'], 
			self::$config['cookie']['httpOnly'] 
		);

		session_name(self::$config['cookie']['name']);

		$this->updateSessionId($sessionId);

		if ($sessionId && headers_sent())
			@session_start(); // In this case, supress headers-sent warning: session ID is available so all ok
		else
			session_start();

	}
	
	public function __set($name, $val) {
		// Magic sesion value setter 
		
		$_SESSION[$name] = $val;

	}
	
	public function __unset($name) {
		// Magic session unsetter

		unset($_SESSION[$name]);
		
	}
	
	public function __get($name) {
		// Magic sesion value getter 
		
		if (!isset($_SESSION[$name])) return null;
		return $_SESSION[$name];
		
	}
	
	public function destroy() {
		// Remove all data from and traces of the current session
		
		session_destroy();
		
	}

	public function updateSessionId($sid = false) {

		// If not provided, retrieve it 
		// (must get it from cookie, session_id() doesn't return value until after session_start())
		if (!$sid) $sid = $_COOKIE[self::$config['cookie']['name']];
		// If provided, be sure we're using it
		else session_id($sid);

		$sid = preg_replace('/[^A-Za-z0-9\+\/=]/', '', $sid);

		while (strlen($sid) == 50) {

			$sids = explode('=', $sid);
			if (sizeof($sids) != 2) break;

			// Verify mini-hmac; not critical, just a quick sanity check
			$check = substr( base64_encode( hash('sha256', $sids[0] .'='. self::$config['validatorSalt'], true) ), 0, 6 );
			if ($sids[1] !== $check) break;

			return $sid;

		}

		// Looks like we need a new session ID
		$sid = base64_encode( hash('sha256', microtime() . $_SERVER['REMOTE_ADDR'] . mt_rand(0, 1000000000), true) );

		// Create mini-hmac; not critical, just a quick sanity check
		$sid .= substr( base64_encode( hash('sha256', $sid . self::$config['validatorSalt'], true) ), 0, 6 );
 
 		session_id($sid);

 		return $sid;

	}

	public function __destruct() {
		// Not required as it is handled automatically but could be convienent to close a session early or juggle multiple sessions
		session_write_close();
	}
	
}
