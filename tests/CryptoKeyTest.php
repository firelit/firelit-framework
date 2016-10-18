<?PHP

use Firelit\CryptoKey;

class CryptoKeyTest extends PHPUnit_Framework_TestCase
{

    public function testGeneration()
    {

        $key = CryptoKey::newSymmetricKey(256);

        // Make sure the key type is set
        $this->assertEquals(CryptoKey::TYPE_SYMMETRIC, $key->getType());
        $this->assertEquals(256, $key->getBitLength());

        // It takes 24 base64 characters to represent 256-bits of data
        $textKey = $key->getKey(CryptoKey::FORMAT_BASE64);
        $this->assertRegExp('/^[A-Za-z0-9\+\=\/]{44}$/', $textKey);

        $key = CryptoKey::newPrivateKey(1024);

        // Make sure the key type is set
        $this->assertEquals(CryptoKey::TYPE_PRIVATE, $key->getType());
        $this->assertEquals(1024, $key->getBitLength());

        // It takes 24 base64 characters to represent 256-bits of data
        $textKey = $key->getKey(CryptoKey::FORMAT_PEM);
        $this->assertRegExp('/^-{5}.+-{5}$/m', $textKey);
        $this->assertRegExp('/PRIVATE/', $textKey);
        $this->assertRegExp('/[A-Za-z0-9\+\=\/\n]{800,}/m', $textKey);

        $textKey = $key->getPublicKey(CryptoKey::FORMAT_PEM);
        $this->assertRegExp('/^-{5}.+-{5}$/m', $textKey);
        $this->assertRegExp('/PUBLIC/', $textKey);
        $this->assertRegExp('/[A-Za-z0-9\+\=\/\n]{219,}/m', $textKey);
    }
}
