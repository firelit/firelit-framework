<?PHP

class ApiResponseXMLTest extends PHPUnit_Framework_TestCase {
	
	protected $api;
	
	protected function setUp() {
		
		$this->api = Firelit\ApiResponse::init('XML');
		
	}
	
	public function testTemplate() {
	
		ob_start();
		
		$this->api->setTemplate(array('res' => true));
		$this->api->respond();
		
		$res = ob_get_contents();
		ob_end_clean();

		$this->assertRegExp( '!^<\?xml version="1.0"\?>!', $res );
		$this->assertRegExp( '!<response><res>true</res></response>!', $res );
		
	}
	
	protected function tearDown() {
	
		unset($this->api);
		
	}
	
}