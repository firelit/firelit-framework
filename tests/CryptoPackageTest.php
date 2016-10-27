<?PHP

namespace Firelit;

class CryptoPackageTest extends \PHPUnit_Framework_TestCase
{

    private $secret;

    protected function setUp()
    {

        $this->secret = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

        $this->object = (object) array('key' => 'value123', 'text' => $this->secret);
    }

    public function testString()
    {

        $key = CryptoKey::newPrivateKey(2048);

        $packager = new CryptoPackage($key);
        $ciphertext = $packager->encrypt($this->secret)->with($packager::PUBLIC_KEY);

        $this->assertTrue(strlen($ciphertext) > 400);
        $this->assertEquals(0, preg_match('/Lorem ipsum/', $ciphertext));
        $this->assertNotEquals($ciphertext, $this->secret);

        $packager = new CryptoPackage($key);
        $back = $packager->decrypt($ciphertext)->with($packager::PRIVATE_KEY);

        $this->assertEquals($this->secret, $back);
    }

    public function testObject()
    {

        $key = CryptoKey::newPrivateKey(2048);

        $packager = new CryptoPackage($key);
        $ciphertext = $packager->encrypt($this->object)->with($packager::PRIVATE_KEY);

        $this->assertTrue(strlen($ciphertext) > 500);
        $this->assertEquals(0, preg_match('/Lorem ipsum/', $ciphertext));

        $packager = new CryptoPackage($key);
        $back = $packager->decrypt($ciphertext)->with($packager::PUBLIC_KEY);

        $this->assertEquals($this->object, $back);
    }
}
