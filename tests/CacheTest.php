<?PHP

class CacheTest extends PHPUnit_Framework_TestCase {
	
	private $session, $store, $testVal;
	
	protected function setUp() {
		
		Firelit\Cache::config(array(
			'memecached' => array(
				'enabled' => false
			)
		));
		
	}
	
	public function testGetNoClosure() {
		
		$val = Firelit\Cache::get('index');
		
		$this->assertNull($val);
		
	}
	
	/**
	* @depends testGetNoClosure
	*/
	public function testGetCacheMiss() {
		
		$val = Firelit\Cache::get('index', function() {
			
			return 12345;
			
		});
		
		$this->assertEquals( 12345, $val );
		$this->assertFalse( Firelit\Cache::$cacheHit );
		
	}
	
	/**
	* @depends testGetCacheMiss
	*/
	public function testVariableSeperation() {
	
		$val = Firelit\Cache::get('index2', function() {
			
			return 100;
			
		});
		
		$this->assertEquals( 100, $val );
		$this->assertFalse( Firelit\Cache::$cacheHit );
		
	}
	
	/**
	* @depends testGetCacheMiss
	*/
	public function testGetCacheHit() {
	
		$val = Firelit\Cache::get('index', function() {
			
			return -1;
			
		});
		
		$this->assertEquals( 12345, $val );
		$this->assertTrue( Firelit\Cache::$cacheHit );
		
	}
	
	/**
	* @depends testGetCacheHit
	*/
	public function testGetCacheSet() {
	
		$val = Firelit\Cache::get('index');

		$this->assertEquals( 12345, $val );

		Firelit\Cache::set('index', 123456);
		
		$val = Firelit\Cache::get('index');

		$this->assertEquals( 123456, $val );
		
		Firelit\Cache::set('index', null);
		
		$val = Firelit\Cache::get('index');

		$this->assertNull( $val );
		
	}
}