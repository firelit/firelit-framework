<?PHP

namespace Firelit;

class Crypto {
	
	const AES_256 = MCRYPT_RIJNDAEL_256;
	const CFB = MCRYPT_MODE_CFB;

	/**
	 * config()
	 * @param array $config Updated configuration array
	 */
	public static function config($config) {
		self::$config = array_merge(self::$config, $config);	
	}
	
	/**
	 * getIv()
	 * @return string Return an initializtion vector appropriate for cipher
	 */
	public static function getIv() {
		$size = mcrypt_get_iv_size(self::AES_256, self::CFB);
		$iv = mcrypt_create_iv($size, MCRYPT_RAND);
		return base64_encode($iv);
	}
	
	/**
	 * encrypt()
	 * @param string $text The string to encrypt
	 * @param string $key The encryption key
	 * @param string $iv An initialization vector
	 * @return string Return an cipher text, base64-encoded
	 */
	public static function encrypt($text, $key, $iv) { 
		$iv = base64_decode($iv);
		$key = base64_decode($key);
		$enc = mcrypt_encrypt(self::AES_256, $key, $text, self::CFB, $iv);
		return trim(base64_encode($enc)); 
	} 
	
	/**
	 * decrypt()
	 * @param string $enc The cipher text to decrypt, base64-encoded
	 * @param string $key The encryption key
	 * @param string $iv The initialization vector
	 * @return string Return the unencoded text
	 */
	public static function decrypt($enc, $key, $iv) { 
		$iv = base64_decode($iv);
		$enc = base64_decode($enc);
		$key = base64_decode($key);
		$text = mcrypt_decrypt(self::AES_256, $key, $enc, self::CFB, $iv);
		return trim($text); 
	} 

	/**
	 * keyHexToBinary()
	 * @param string $hex A hexadecimal string
	 * @return string Returns a binary representation
	 */
	public static function keyHexToBinary($hex) {
		return $key = pack('H*', $hex);
	}

	/**
	 * generateKey()
	 * @param string $bytes The length of the key in bytes
	 * @return string Returns a randomly generated, base64-encoded key of appropriate length
	 */
	public static function generateKey($bytes = 32) {

		// Mixin' it up
		$key = mcrypt_create_iv(floor($bytes/2), MCRYPT_RAND);
		usleep(mt_rand(1,10));
		$key .= mcrypt_create_iv(ceil($bytes/2), MCRYPT_RAND);

		return base64_encode($key);
	}

	/**
	 * package()
	 * @param string $text The text to encrypt
	 * @param string $key The encryption key
	 * @return string Returns a neatly packaged encryption string (Format: "{IV}|{Cipher Text}|{HMAC}")
	 */
	public static function package($text, $key) {
		$iv = self::getIv();
		$enc = self::encrypt($text, $key, $iv);

		$part1 = $iv .'|'. $enc;
		$hmac = base64_encode(hash_hmac('sha256', $part1, $key, true));

		return $part1 .'|'. $hmac;
	}

	/**
	 * unpackage()
	 * @param string $pckg An encryption package including all needed parts (Format: "{IV}|{Cipher Text}|{HMAC}")
	 * @param string $key The encryption key
	 * @return string Returns the decrypted text upon success (returns null or false upon failure)
	 */
	public static function unpackage($pckg, $key) {
		list($iv, $enc, $hmac) = explode('|', $pckg);

		$part1 = $iv .'|'. $enc;
		$hmacComp = base64_encode(hash_hmac('sha256', $part1, $key, true));

		if ($hmac !== $hmacComp) return null;

		return self::decrypt($enc, $key, $iv);
	}

}
