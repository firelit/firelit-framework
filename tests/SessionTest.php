<?PHP

class SessionTest extends PHPUnit_Framework_TestCase {
	
	private $session, $store, $testVal;
	
	protected function setUp() {
		
		$this->testVal = 'A test value';
		
		$this->store = $this->getMock('Firelit\SessionStore', array('store', 'fetch', 'destroy'));
		
		$this->session = new Firelit\Session($this->store);
		
	}
		
	public function testSet() {
	
		$varName = 'test'. mt_rand(0, 1000);
		
		$this->store->expects($this->once())
					->method('store')
					->with($this->equalTo(array( $varName => $this->testVal)));
                 
		$this->session->$varName = $this->testVal;
		
		$this->session->save();
		
	}
		
	public function testGet() {
	
		$varName = 'test'. mt_rand(0, 1000);
		
		$this->store->expects($this->once())
					->method('fetch')
					->will($this->returnValue(array( $varName => $this->testVal)));
                 
		$this->assertEquals($this->session->$varName, $this->testVal);
		
	}
	
	public function testGetOnlyOnce() {
	
		$varName = 'test'. mt_rand(0, 1000);
		
		$this->store->expects($this->once())
					->method('fetch')
					->will($this->returnValue(array( $varName => $this->testVal)));
                 
		$firstGet = $this->session->$varName;
		
		// Second get should be from object cache
		$this->assertEquals($this->session->$varName, $this->testVal);
		
	}
	
	public function testUnset() {
	
		$varName = 'test'. mt_rand(0, 1000);
		
		$this->store->expects($this->once())
					->method('store')
					->with($this->equalTo(array()));
                 
		unset($this->session->$varName);
		
		$this->session->save();
				
	}
	
	
	public function testDestroy() {
	
		$this->store->expects($this->once())
					->method('destroy');
					
		$this->session->destroy();
		
	}
}