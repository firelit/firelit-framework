<?PHP

namespace Firelit;

/**
 *  Important: Public key encryption will not work for longer strings (>80 characters, related to key size)
 */
class Crypto
{

    const   PUBLIC_KEY = 'PUB',
            PRIVATE_KEY = 'PRIV';

    const   ACTION_ENCRYPT = 'ENC',
            ACTION_DECRYPT = 'DEC';

    const   DEFAULT_AES_MODE = 'CFB';

    protected $key;
    protected $subject;
    protected $action;
    protected $aesMode;

    /**
     * Constructor
     * @param CryptoKey $key Key to be used for encryption/decryption
     */
    public function __construct(CryptoKey $key, $aesMode = self::DEFAULT_AES_MODE)
    {

        $this->key = $key;
        $this->aesMode = $aesMode;
    }

    /**
     *  Returns encrypted data if using symmetric key, otherwise returns $this for chaining to with() method
     *  @param $plainText Data/string to encrypt
     *  @return $this (with private key) or raw encrypted data (with symmetric key)
     */
    public function encrypt($plainText)
    {

        if ($this->key->getType() == CryptoKey::TYPE_SYMMETRIC) {
            $bitLen = $this->key->getBitLength();
            $method = 'AES-'. $bitLen .'-'. $this->aesMode;
            $encryptionKey = $this->key->getKey(CryptoKey::FORMAT_RAW);
            $ivLen = openssl_cipher_iv_length($method);
            $iv = openssl_random_pseudo_bytes($ivLen);

            return $iv . openssl_encrypt($plainText, $method, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        }

        $this->action = self::ACTION_ENCRYPT;
        $this->subject = $plainText;

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
            $bitLen = $this->key->getBitLength();
            $method = 'AES-'. $bitLen .'-'. $this->aesMode;
            $encryptionKey = $this->key->getKey(CryptoKey::FORMAT_RAW);
            $ivLen = openssl_cipher_iv_length($method);
            $iv = substr($subject, 0, $ivLen);

            return openssl_decrypt(substr($subject, $ivLen), $method, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        }

        $this->action = self::ACTION_DECRYPT;
        $this->subject = $subject;

        return $this;
    }

    /**
     *  Specifies what type of key to use with the action specified (only for private/public key cryptography)
     *  @param $keyType Specify the key type to use (see constants)
     */
    public function with($keyType)
    {

        if (empty($this->action) || empty($this->subject)) {
            throw new \Exception('Must set an action with encrypt() or decrypt()');
        }

        if ($this->key->getType() != CryptoKey::TYPE_PRIVATE) {
            throw new \Exception('Can only specify key to use when using asymmetric cryptogrpahy');
        }

        if ($keyType == self::PUBLIC_KEY) {
            $publicKey = $this->key->getPublicKey(CryptoKey::FORMAT_RAW);
        } elseif ($keyType == self::PRIVATE_KEY) {
            $privateKey = $this->key->getKey(CryptoKey::FORMAT_RAW);
            $privateKey = openssl_pkey_get_private($privateKey);
        }

        $out = '';
        $success = false;

        if ($this->action == self::ACTION_ENCRYPT) {
            if ($keyType == self::PUBLIC_KEY) {
                $success = openssl_public_encrypt($this->subject, $out, $publicKey, OPENSSL_PKCS1_PADDING);
            } elseif ($keyType == self::PRIVATE_KEY) {
                $success = openssl_private_encrypt($this->subject, $out, $privateKey, OPENSSL_PKCS1_PADDING);
            }
        } elseif ($this->action == self::ACTION_DECRYPT) {
            if ($keyType == self::PUBLIC_KEY) {
                $success = openssl_public_decrypt($this->subject, $out, $publicKey, OPENSSL_PKCS1_PADDING);
            } elseif ($keyType == self::PRIVATE_KEY) {
                $success = openssl_private_decrypt($this->subject, $out, $privateKey, OPENSSL_PKCS1_PADDING);
            }
        }

        if (!$success) {
            throw new \Exception('Failure to '. $this->action .' with '. $keyType .' key ('. openssl_error_string() .')');
        }

        return $out;
    }
}
