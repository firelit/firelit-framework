<?PHP

class CryptoTest extends PHPUnit_Framework_TestCase {
	
	private $encrypted, $password, $iv, $unencrypted;
	
	protected function setUp() {
		
		$this->password = base64_encode('My super secret password!');
		$this->unencrypted = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut urna tellus, faucibus nec porttitor ac, laoreet tristique libero.';

	}
	
	public function testIv() {
		
		$this->iv = Firelit\Crypto::getIv();
		$this->assertRegExp('/^[\w=\/\+]{5,}$/', $this->iv);
		
		return $this->iv;
		
	}

	public function testKeyGen() {
		
		$key = Firelit\Crypto::generateKey();
		$this->assertRegExp('/^[0-9A-Za-z\\=\+\/]{44}$/', $key);
		
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
	
	public function testPackages() {
			
		$subject = 'This is a test cipher. Encrypt me!';
		$password = Firelit\Crypto::generateKey(32);

		$package = Firelit\Crypto::package($subject, $password);
		
		$this->assertRegExp('/^[0-9A-Za-z\\=\+\/\|]{20,}$/', $package);

		$failedUnPackage = Firelit\Crypto::unpackage($package, $password.'1');

		$this->assertNull($failedUnPackage, 'Unpackaging should fail due to invalid HMAC');

		$successUnPackage = Firelit\Crypto::unpackage($package, $password);

		$this->assertTrue($subject === $successUnPackage, 'Unpackaging should be successfull');
		
	}
}