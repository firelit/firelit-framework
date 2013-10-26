<?PHP

class ApiResponseJSONTest extends PHPUnit_Framework_TestCase {
	
	protected $api;
	
	protected function setUp() {
		
		$this->api = Firelit\ApiResponse::init('JSON');
		
	}
	
	public function testTemplate() {
	
		ob_start();
		
		$this->api->setTemplate(array('res' => true));
		$this->api->respond();
		
		$res = ob_get_contents();
		ob_end_clean();
		
		$this->assertEquals( '{"res":true}', $res );
		
	}
	
	public function testCallback() {
	
		ob_start();
		
		$this->api->jsonCallback = 'functionName';
		$this->api->setTemplate(array('res' => true));
		$this->api->respond();
		
		$res = ob_get_contents();
		ob_end_clean();
		
		$this->assertEquals( 'functionName({"res":true});', $res );
		
	}
	
	protected function tearDown() {
	
		unset($this->api);
		
	}
	
}