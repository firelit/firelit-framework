<?PHP

namespace Firelit;

class Cache {
	// Caching class
	
	// Global connection & state variables 
	public static $config = array(
		'memcached' => array(
			'enabled' => false,
			'servers' => array()
		)
	);
	
	public static $memcached = false, $cache = array();
	
	public static $cacheHit = false, $cacheMiss = false;
	
	public function __construct() { }	

	public static function config($config) {
		
		self::$config = array_merge(self::$config, $config);
			
	}
	
	public static function get($name, $closure = false) {
		
		if (!is_string($name))
			throw new \Exception('Cache key must be a string.');
			
		// First check php-memory cache
		if (isset(self::$cache[$name])) {
			
			// Cache hit!
			self::$cacheHit = true;
			self::$cacheMiss = false;
			
			return self::$cache[$name];
			
		}
		
		if (self::$config['memcached']['enabled']) {
		
			// Connect if not connected
			if (!self::$memcached) self::memcachedConnect();
			
			// Check if in memcache
			$val = self::$memcached->get($name);
			
			if (self::$memcached->getResultCode() != \Memcached::RES_NOTFOUND) {
				
				// Cache hit!
				self::$cacheHit = true;
				self::$cacheMiss = false;
				
				// Set php-memory cache
				self::$cache[$name] = $val;
				return $val;
			
			}
			
		}
		
		self::$cacheHit = false;
		self::$cacheMiss = true;
		
		// If no other way to get value, return
		if (!is_callable($closure)) return null;
		
		// Call given closure to get value
		$val = $closure();
		
		// Nothing returned, no need to store it
		if (is_null($val)) return null;
		
		// Store in php-memory cache
		self::$cache[$name] = $val;
		
		if (self::$config['memcached']['enabled']) {
			
			// Store in memcache
			self::$memcached->set($name, $val);
		
		}
		
		return $val;
		
	}
	
	public static function memcachedConnect() {
		
		if (self::$memcached) return;
		
		self::$memcached = new \Memcached();
		
		self::$memcached->addServers( self::$config['memcached']['servers'] );
	
	}

}