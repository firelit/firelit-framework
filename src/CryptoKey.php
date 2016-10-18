<?php
/**
 *  A general class for holding keys for both symmetric and private key encryption.
 *  Assuming that AES is used for symmetric key encryption.
 *  Assuming that RSA is used for private key encryption.
 */
namespace Firelit;

class CryptoKey
{

    const   FORMAT_RAW = 'RAW',
            FORMAT_BASE64 = 'BASE64',
            FORMAT_HEX = 'HEX',
            FORMAT_PEM = 'PEM';

    const   TYPE_SYMMETRIC = 'SYMMETRIC',
            TYPE_PRIVATE = 'PRIVATE';

    private $type;
    private $key;
    private $bits;

    public function setKey($key, $type)
    {

        if ($type == static::TYPE_SYMMETRIC) {
            $len = strlen($key);
            $this->bits = $len * 8;
            $this->key = $key;
        } elseif ($type == static::TYPE_PRIVATE) {
            if (gettype($key) == 'resource') {
                // Store the key resource
                $this->key = $key;
            } elseif (gettype($key) == 'string') {
                // Import the given key
                $this->key = openssl_pkey_get_private($key);
            } else {
                throw new \Exception('Private key given in unsupported format (must be PEM encoded string or a key resource)');
            }

            $details = openssl_pkey_get_details($this->key);
            $this->bits = $details['bits'];
        } else {
            throw new \Exception('Invalid key type');
        }

        $this->type = $type;
    }

    public function getType()
    {

        return $this->type;
    }

    public function getBitLength()
    {

        return $this->bits;
    }

    public function getKey($format = false)
    {

        if (!$format) {
            // Default formats for given type:
            if ($this->type == static::TYPE_SYMMETRIC) {
                $format = self::FORMAT_BASE64;
            }

            if ($this->type == static::TYPE_PRIVATE) {
                $format = self::FORMAT_PEM;
            }
        }

        switch ($format) {
            case self::FORMAT_RAW:
                return $this->key;

            case self::FORMAT_BASE64:
                if ($this->type != static::TYPE_SYMMETRIC) {
                    throw new \Exception('Invalid formatting for symmetric key');
                }

                return base64_encode($this->key);

            case self::FORMAT_HEX:
                if ($this->type != static::TYPE_SYMMETRIC) {
                    throw new \Exception('Invalid formatting for symmetric key');
                }

                return unpack('H*', $this->key);

            case self::FORMAT_PEM:
                if ($this->type != static::TYPE_PRIVATE) {
                    throw new \Exception('Invalid formatting for private key');
                }

                $out = '';
                openssl_pkey_export($this->key, $out, null, array(
                    'config' => dirname(__DIR__) .'/config/openssl.cnf'
                ));
                return $out;

            default:
                throw new \Exception('Invalid format');
        }
    }

    public function getPublicKey($format = self::FORMAT_RAW)
    {
        if ($this->type != static::TYPE_PRIVATE) {
            throw new \Exception('Public keys can only be generated from private key');
        }

        $pubKey = openssl_pkey_get_details($this->key);
        $pubKey = $pubKey['key'];

        switch ($format) {
            case self::FORMAT_RAW:
                return openssl_pkey_get_public($pubKey);

            case self::FORMAT_PEM:
                return $pubKey;

            default:
                throw new \Exception('Invalid format');
        }
    }

    public static function newSymmetricKey($bits = 256)
    {

        if (!in_array($bits, array(128, 192, 256))) {
            throw new \Exception('Invalid key length: '. $bits .' bits');
        }

        $keyclass = get_called_class();
        $ckey = new $keyclass;

        $success = false;

        $rand = openssl_random_pseudo_bytes($bits / 8, $success);

        if (!$success || empty($rand)) {
            throw new \Exception('Could not generate a secure key');
        }

        $ckey->setKey($rand, static::TYPE_SYMMETRIC);

        return $ckey;
    }

    public static function newPrivateKey($bits = 2048)
    {

        if (!in_array($bits, array(1024, 2048, 3072))) {
            throw new \Exception('Invalid key length: '. $bits .' bits');
        }

        $keyclass = get_called_class();
        $ckey = new $keyclass;

        $success = false;

        $key = openssl_pkey_new(array(
            'config' => dirname(__DIR__) .'/config/openssl.cnf',
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'encrypt_key' => false
        ));

        if ($key === false) {
            throw new \Exception('Could not generate a key ('. openssl_error_string() .')');
        }

        $ckey->setKey($key, static::TYPE_PRIVATE);

        return $ckey;
    }
}
