<?PHP

class CheckHTTPSTest extends PHPUnit_Framework_TestCase {
	
	public function testSecureCheck() {
		
		$this->assertFalse( Firelit\CheckHTTPS::isSecure() );
		
	}
	
	public function testSecureURL() {
	
		$_SERVER['SERVER_NAME'] = 'example.com';
		$_SERVER['REQUEST_URI'] = '/test.php';
		
		$this->assertEquals( 'https://example.com/test.php', Firelit\CheckHTTPS::getSecureURL() );
		
	}
}