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

	private $session_id;

	public function __construct(\SessionHandlerInterface $store = null, $sessionId = false, $validateSessionId = true) {	
		// Create a session using the given SessionHandlerInterface object
		// If null, will use PHP's native SESSION engine

		if ($store) session_set_save_handler($store, true);

		// Check for Session ID in cookies
		if (!$sessionId)
			$sessionId = $this->getSessionId();

		// Generate Session ID if none available
		if (!$sessionId)
			$sessionId = $this->generateSessionId();

		// Set the Session ID and send cookie with value
		$this->setSessionId($sessionId, $validateSessionId);

		@session_start();

	}
	
	public function __set($name, $val) {
		// Magic sesion value setter 
		
		$_SESSION[$name] = $val;

	}
	
	public function __get($name) {
		// Magic sesion value getter 
		
		if (!isset($_SESSION[$name])) return null;
		return $_SESSION[$name];
		
	}
	
	public function __isset($name) {
		// Magic session isset

		return isset($_SESSION[$name]);
		
	}
	
	public function __unset($name) {
		// Magic session unsetter

		unset($_SESSION[$name]);
		
	}
	
	// Can only be excuted before session_start()
	protected function setSessionId($sessionId, $validateSessionId = true) {

		if ($validateSessionId) {
			if (!$this->sessionIdIsValid($sessionId))
				$sessionId = false;
		}

		if (!$sessionId)
			$sessionId = $this->generateSessionId();

		$this->session_id = $sessionId;

		// Not relying on session to retrieve it's ID from the cookie
		session_id($sessionId);

		// Will set cookie automatically anyway, let's try to control it
		session_set_cookie_params(
			self::$config['cookie']['lifetime'], 
			self::$config['cookie']['path'], 
			self::$config['cookie']['domain'], 
			self::$config['cookie']['secureOnly'], 
			self::$config['cookie']['httpOnly'] );

		session_name(self::$config['cookie']['name']);

	}

	public function getSessionId() {
		
		if (!$this->session_id) {

			if (isset($_COOKIE[self::$config['cookie']['name']]))
				$this->session_id = $_COOKIE[self::$config['cookie']['name']];

		}

		return $this->session_id;

	}

	public static function sessionIdIsValid($sessionId) {

		$sessionId = preg_replace('/[^A-Za-z0-9\+\/=]+/', '', $sessionId);

		if (strlen($sessionId) != 50) return false;

		$sids = explode('=', $sessionId);
		if (sizeof($sids) != 2) return false;

		// Verify mini-hmac; not critical, just a quick sanity check
		$check = static::generateHmacSid($sids[0].'=', static::$config['validatorSalt']);

		if ($sessionId !== $check) return false;

		return true;

	}

	public static function generateSessionId() {

		if (isset($_SERVER['REMOTE_ADDR'])) $remAddr = $_SERVER['REMOTE_ADDR'];
		else $remAddr = mt_rand(0, 1000000);

		if (isset($_SERVER['REMOTE_PORT'])) $remPort = $_SERVER['REMOTE_PORT'];
		else $remPort = mt_rand(0, 1000000);

		// Looks like we need a new session ID
		$sid = base64_encode( hash('sha256', microtime() .'|'. $remAddr .'|'. $remPort .'|'. mt_rand(0, 1000000), true) );

		// Create mini-hmac; not critical, just a quick sanity check
		return static::generateHmacSid($sid, static::$config['validatorSalt']);

	}

	public static function generateHmacSid($partSid, $salt) {

		return $partSid . substr( base64_encode( hash_hmac('sha256', $partSid, $salt, true) ), 0, 6 );

	}

	public function destroy() {
		// Remove all data from and traces of the current session
		
		session_destroy();
		
	}

	public function __destruct() {
		// Not required as it is handled automatically but could be convienent to close a session early or juggle multiple sessions
		session_write_close();
	}
	
}
