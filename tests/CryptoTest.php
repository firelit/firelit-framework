<?PHP

namespace Firelit;

class CryptoTest extends \PHPUnit_Framework_TestCase
{

    private $secret;

    protected function setUp()
    {

        $this->secret = 'This is a secret phrase!';
    }

    public function testSymmetric()
    {

        $key = CryptoKey::newSymmetricKey(256);

        $crypto = new Crypto($key);
        $ciphertext = $crypto->encrypt($this->secret);

        $this->assertTrue(strlen($ciphertext) > 20);
        $this->assertNotEquals($ciphertext, $this->secret);

        $crypto = new Crypto($key);
        $back = $crypto->decrypt($ciphertext);

        $this->assertEquals($this->secret, $back);
    }

    public function testPublicKey()
    {

        $key = CryptoKey::newPrivateKey(1024);

        $crypto = new Crypto($key);
        $ciphertext = $crypto->encrypt($this->secret)->with($crypto::PUBLIC_KEY);

        $this->assertTrue(strlen($ciphertext) > 20);
        $this->assertNotEquals($ciphertext, $this->secret);

        $crypto = new Crypto($key);
        $back = $crypto->decrypt($ciphertext)->with($crypto::PRIVATE_KEY);

        $this->assertEquals($this->secret, $back);

        // Now let's try it the other way around

        $key = CryptoKey::newPrivateKey(1024);

        $crypto = new Crypto($key);
        $ciphertext = $crypto->encrypt($this->secret)->with($crypto::PRIVATE_KEY);

        $this->assertTrue(strlen($ciphertext) > 20);
        $this->assertNotEquals($ciphertext, $this->secret);

        $crypto = new Crypto($key);
        $back = $crypto->decrypt($ciphertext)->with($crypto::PUBLIC_KEY);

        $this->assertEquals($this->secret, $back);
    }
}
