<?PHP

namespace Firelit;

class Crypto
{

    const   PUBLIC_KEY = 'PUB',
            PRIVATE_KEY = 'PRIV';

    private $key, $subject, $action;

    /**
     * Constructor
     * @param CryptoKey $key Key to be used for encryption/decryption
     */
    public function __construct(CryptoKey $key)
    {

        $this->key = $key;

    }

    public function encrypt($plainText) {

        $this->action = 'encrypt';
        $this->subject = $plainText;

        return $this;
    }

    public function decrypt($cryptogram) {

        $this->action = 'decrypt';
        $this->subject = $cryptogram;

        return $this;
    }

    public function with($keyType = false) {

        if (empty($this->action) || empty($this->subject)) {
            throw new \Exception('Must set an action: encrypt() or decrypt()')
        }

        if ($this->key->getType() == CryptoKey::TYPE_SYMMETRIC) {

            if ($this->action )
        }
        if ($keyType == static::SYMMETRIC_KEY) {
            if ($this->key->getType() != CryptoKey::TYPE_SYMMETRIC) {

            }
        }
    }

}
