<?PHP

namespace Firelit;

class Cache
{

    // Global connection & state variables
    public static $config = array(
        'memcached' => array(
            'enabled' => false,
            'servers' => array()
        )
    );

    protected static $memcached = false;
    protected static $cache = array();

    // Default settings for each memcached server
    public static $memcachedServerDefaults = array(
        'host' => 'localhost',
        'port' => 11211,
        'persistent' => true,
        'weight' => 1,
        'timeout' => 1
    );

    // Boolean indicating if a get() resulted in a cache hit
    public static $cacheHit = false;

    /**
     * __construct()
     */
    public function __construct()
    {
    }

    /**
     * config()
     * @param array $config Updated configuration array
     */
    public static function config($config)
    {

        self::$config = array_merge(self::$config, $config);
    }

    /**
     * get()
     * @param string $name Name of variable as stored in cache
     * @param string $closure Optional closure to get value if cache miss
     * @return mixed The cached value (or closure-returned value, if cache miss), null if cache-miss and no closure
     */
    public static function get($name, $closure = false)
    {

        if (!is_string($name)) {
            throw new \Exception('Cache key must be a string.');
        }

        // First check php-memory cache
        if (isset(self::$cache[$name])) {
            // Cache hit!
            self::$cacheHit = true;
            return self::$cache[$name];
        }

        if (self::$config['memcached']['enabled']) {
            // Connect if not connected
            self::memcachedConnect();

            // Check if in memcached
            $val = self::$memcached->get($name);

            if (self::$memcached->getResultCode() == \Memcached::RES_SUCCESS) {
                // Cache hit!
                self::$cacheHit = true;

                // Set php-memory cache
                self::$cache[$name] = $val;
                return $val;
            }
        }

        self::$cacheHit = false;

        // If no other way to get value, return
        if (!is_callable($closure)) {
            return null;
        }

        // Call given closure to get value
        $val = $closure();

        // Nothing returned, no need to store it
        if (is_null($val)) {
            return null;
        }

        // Store closure-returned value in cache
        self::set($name, $val);

        return $val;
    }

    /**
     * set()
     * @param string $name Name of variable to be stored in cache
     * @param string $val Value of variable to be stored, null to delete value from cache
     */
    public static function set($name, $val)
    {

        if (!is_string($name)) {
            throw new \Exception('Cache key must be a string.');
        }

        // Connect if not connected
        if (self::$config['memcached']['enabled']) {
            self::memcachedConnect();
        }

        if (is_null($val)) {
            // If $val is null, remove from cache
            unset(self::$cache[$name]);

            // Remove from memcached
            if (self::$config['memcached']['enabled']) {
                self::$memcached->delete($name);
            }
        } else {
            // Store in php-memory cache
            self::$cache[$name] = $val;

            // Store in memcached
            if (self::$config['memcached']['enabled']) {
                self::$memcached->set($name, $val);
            }
        }
    }

    /**
     * memcachedConnect()
     */
    public static function memcachedConnect()
    {

        if (self::$memcached) {
            return;
        }

        self::$memcached = new \Memcached();

        foreach (self::$config['memcached']['servers'] as $server) {
            extract(array_merge(self::$memcachedServerDefaults, $server));

            self::$memcached->addServers($host, $port, $persistent, $weight, $timeout);
        }
    }
}
