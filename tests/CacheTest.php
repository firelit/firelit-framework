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
		$this->assertTrue( Firelit\Cache::$cacheMiss );
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
		$this->assertTrue( Firelit\Cache::$cacheMiss );
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
		$this->assertFalse( Firelit\Cache::$cacheMiss );
		$this->assertTrue( Firelit\Cache::$cacheHit );
		
	}
	
}