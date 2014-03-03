<?PHP

class ApiResponseTest extends PHPUnit_Framework_TestCase {
	
	public function testTemplate() {
		
		ob_start();
		
		$resp = Firelit\ApiResponse::init('JSON', false);

		$resp->setTemplate(array('res' => true, 'message' => 'Peter picked a pack of pickels'));
		$resp->respond(array(), false);
		
		unset($resp);
		Firelit\ApiResponse::destruct();

		$res = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('{"res":true,"message":"Peter picked a pack of pickels"}', $res);
		
	}
	
	public function testCancel() {
		
		ob_start();
		
		$resp = Firelit\ApiResponse::init('JSON', false);

		$resp->setTemplate(array('res' => true, 'message' => 'Peter picked a pack of pickels'));

		$resp->cancel();

		echo '<HTML>';
		
		unset($resp);
		Firelit\ApiResponse::destruct();

		$res = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('<HTML>', $res);
		
	}
	
}