<?PHP

namespace Firelit;

/**
 *  Encrypts any type of variable with public- (serializes)
 */
class CryptoPackage extends Crypto
{

    private $secondSubject;

    /**
     *  Returns encrypted data if using symmetric key, otherwise returns $this for chaining to with() method
     *  @param $plainText Data/string to encrypt
     *  @return $this (with private key) or raw encrypted data (with symmetric key)
     */
    public function encrypt($subject)
    {

        $subject = serialize($subject);

        if ($this->key->getType() == CryptoKey::TYPE_SYMMETRIC) {
            // Symmetric? This is easy...
            return parent::encrypt($subject);
        }

        // If we're not encrypting with symmetric cryptography, we need to encrypt
        // the data with symmetric and then encrypt the key with the chosen method
        $secondKey = CryptoKey::newSymmetricKey();
        $secondCrypto = new Crypto($secondKey);

        $this->secondSubject = $secondCrypto->encrypt($subject);
        $this->subject = $secondKey->getKey(CryptoKey::FORMAT_RAW);
        $this->action = self::ACTION_ENCRYPT;

        return $this;
    }

    /**
     *  Returns decrypted data if using symmetric key, otherwise returns $this for chaining to with() method
     *  @param $subject Data/string to encrypt
     *  @return $this (with private key) or decrypted data (with symmetric key)
     */
    public function decrypt($subject)
    {

        if ($this->key->getType() == CryptoKey::TYPE_SYMMETRIC) {
            // Symmetric? This is easy...
            return parent::decrypt($subject);
        }

        $splitSubject = explode('|', $subject);
        if (sizeof($splitSubject) != 2) {
            throw new Exception('Not a valid ciphertext for CryptoPackage, try the Crypto class');
        }

        $this->action = self::ACTION_DECRYPT;
        $this->subject = base64_decode($splitSubject[0]);
        $this->secondSubject = base64_decode($splitSubject[1]);

        return $this;
    }

    /**
     *  Specifies what type of key to use with the action specified (only for private/public key cryptography)
     *  @param $keyType Specify the key type to use (see constants)
     */
    public function with($keyType)
    {

        if ($this->action == self::ACTION_ENCRYPT) {
            $encryptedKey = parent::with($keyType);

            return base64_encode($encryptedKey) .'|'. base64_encode($this->secondSubject);
        } elseif ($this->action == self::ACTION_DECRYPT) {
            $decryptedKey = parent::with($keyType);

            $secondKey = new CryptoKey($decryptedKey, CryptoKey::TYPE_SYMMETRIC);
            $secondCrypto = new Crypto($secondKey);

            $plaintext = $secondCrypto->decrypt($this->secondSubject);

            return unserialize($plaintext);
        }
    }
}
