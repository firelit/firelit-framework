<?PHP

class CryptoTest extends PHPUnit_Framework_TestCase {
	
	private $encrypted, $password, $iv, $unencrypted;
	
	protected function setUp() {
		
		$this->password = 'My super secret password!';
		$this->unencrypted = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut urna tellus, faucibus nec porttitor ac, laoreet tristique libero.';

	}
	
	public function testIv() {
		
		$this->iv = Firelit\Crypto::getIv();
		$this->assertRegExp('/^[\w=\/\+]{5,}$/', $this->iv);
		
		return $this->iv;
		
	}

	/**
	 * @depends testIv
	 */
	public function testEncrypt($iv) {
	
		$this->iv = $iv;
		
		$this->encrypted = Firelit\Crypto::encrypt($this->unencrypted, $this->password, $this->iv);
		$this->assertRegExp('/^[\w=\/\+]{5,}$/', $this->encrypted);
		$this->assertNotEquals($this->encrypted, $this->unencrypted);
		
		return array($this->iv, $this->encrypted);
		
	}
	
	/**
	 * @depends testEncrypt
	 */
	public function testDecrypt($passed) {

		$this->iv = $passed[0];
		$this->encrypted = $passed[1];
		
		$testVal = Firelit\Crypto::decrypt($this->encrypted, $this->password, $this->iv);
		$this->assertEquals($testVal, $this->unencrypted);
		
	}
	
}