<?PHP

class ApiResponseTest extends PHPUnit_Framework_TestCase {
	
	public function testTemplate() {
		
		ob_start();
		
		$resp = Firelit\ApiResponse::init('JSON', false);

		$resp->setTemplate(array('res' => true, 'message' => 'Peter picked a pack of pickels'));
		$resp->respond(array(), false);
		
		unset($resp);

		$res = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('{"res":true,"message":"Peter picked a pack of pickels"}', $res);
		
	}
	
}