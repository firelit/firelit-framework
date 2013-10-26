<?PHP

namespace Firelit;

class SessionStoreDB extends SessionStore {
	
	private $db, $sid, $sessionAvail;
	
	// Defaults
	private $config = array(
		'tableName' => 'Sessions',
		'sidLength' => 40, // Length of key in characters
		'expireSeconds' => (60 * 60 * 24 * 7), // How long session variables are stored by default (in seconds)
		'cookie' => array(
			'name' => 'firelit-sid',
			'baseUrl' => '/',
			'sslOnly' => false
		)
	);
	
	public function __construct(Query $queryObject, $config = array()) {
		// See the above config array for possible config array keys
		
		$this->db = $queryObject;
		
		// Merge config data with defaults
		self::config($config);
		
		$keyName = $this->config['cookie']['name'];
		
		$sid = false;
		
		if (isset($_COOKIE[$keyName]) && (strlen($_COOKIE[$keyName]) == $this->config['sidLength'])) {
			// Key available
			
			// Sanitize key
			$sid = $_COOKIE[$keyName];
			$sid = preg_replace('/[^0-9A-Za-z]+/', '', $sid);
			
			// After invalid characters removed, too short?
			if (strlen($sid) != $this->config['sidLength']) $sid = false;
			
		}
		
		if (!$sid) {
			// No key, generate
			
			// Too late if headers sent (throw exception later if access attempted)
			if (headers_sent()) return false;
			
			$sid = self::createSid($this->config['sidLength']);
			
			$expire = time() + ( 86400 * 365 * 10 ); // 10 years from now
			$baseUrl = $this->config['cookie']['baseUrl'];
			$sslOnly = $this->config['cookie']['sslOnly'];
			
			// cookie_name, cookie_value, expire_time, path, host, ssl_only, no_js_access
			setcookie($keyName, $sid, $expire, $baseUrl, $_SERVER['HTTP_HOST'], $sslOnly, true);
			
		}
		
		$this->sessionAvail = true;
		$this->sid = $sid;
		
	}
	
	public static function config($config) {
		
		self::$config = array_merge(self::$config, $config);
			
	}
	
	public function store($valueArray) {
		
		if (!$this->sessionAvail)
			throw new \Exception('Session ID could not be set. Session data will be lost.');
		
		$this->db->replace($this->config['tableName'], array(
			'sid' => $this->sid,
			'value' => serialize($valueArray),
			'expires' => array('SQL', 'DATE_ADD(NOW(), INTERVAL '. $this->config['expireSeconds'] .' SECOND)')
		));
		
		if (!$this->db->success(__FILE__, __LINE__))
			throw new \Exception('Error storing session.');
		
	}
	
	public function fetch() {
	
		if (!$this->sessionAvail)
			throw new \Exception('Session ID could not be set. Session data not available.');
		
		$this->db->select($this->config['tableName'], false, "`sid`=:sid AND `expires`>NOW()", array(':sid' => $this->sid), 1);
		
		if (!$this->db->success(__FILE__, __LINE__)) 
			throw new \Exception('Error retrieving session data.');
		
		if ($row = $this->db->getRow()) return unserialize($row['value']);
		else return null;
		
	}
	
	public function destroy() {
		
		// Nothing to destroy
		if (!$this->sessionAvail) return true;
		
		$this->db->delete($this->config['tableName'], "`sid`=:sid", array(':sid' => $this->sid));
		
		return $this->db->success(__FILE__, __LINE__);
		
	}
	
	static function createSid($len) {
		
	  $symArray = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	  $symArrayLen = strlen($symArray);
	  
	  $key = preg_replace('/[^0-9a-zA-Z]+/', '', base64_encode(hash('sha256', microtime() . $_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'], true))); 
	  $key = substr($key, mt_rand(0, 5), round($len / 2));
	  
	  while (strlen($key) < $len)
	  	$key .= substr($symArray, mt_rand() % $symArrayLen, 1); 
	
		return $key;
		
	}
	
	public function cleanExpired() {
		// Clean out expired data
		
		$this->db->delete($this->config['tableName'], "`expires` <= NOW()");
		
		return $this->db->success(__FILE__, __LINE__);
		
	}
	
	static function install(Query $query, $tableName = 'Sessions', $sidLength = 40) {
		
		// One-time install
		// Create the supporting tables in the db
		
		// TODO - TEST! utf8mb4 may not work....
		$sql = "CREATE TABLE IF NOT EXISTS `". $tableName ."` (
			  `sid` varchar(". $sidLength .") NOT NULL COLLATE utf8mb4_unicode_cs,
			  `values` longtext NOT NULL,
			  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `expires` datetime NOT NULL,
			  UNIQUE KEY `sid` (`sid`),
			  KEY `expires` (`expires`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;";
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed!');
			
	}
}