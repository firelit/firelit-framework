<?PHP

namespace Firelit;

class Crypto {
	
	const AES_256 = MCRYPT_RIJNDAEL_256;
	const CFB = MCRYPT_MODE_CFB;

	public static function config($config) {
		self::$config = array_merge(self::$config, $config);	
	}
	
	public static function getIv() {
		$size = mcrypt_get_iv_size(self::AES_256, self::CFB);
		$iv = mcrypt_create_iv($size, MCRYPT_RAND);
		return base64_encode($iv);
	}
	
	public static function encrypt($text, $key, $iv) { 
		$iv = base64_decode($iv);
		$key = base64_decode($key);
		$enc = mcrypt_encrypt(self::AES_256, $key, $text, self::CFB, $iv);
		return trim(base64_encode($enc)); 
	} 
	
	public static function decrypt($enc, $key, $iv) { 
		$iv = base64_decode($iv);
		$enc = base64_decode($enc);
		$key = base64_decode($key);
		$text = mcrypt_decrypt(self::AES_256, $key, $enc, self::CFB, $iv);
		return trim($text); 
	} 

	public static function keyHexToBinary($hex) {
		return $key = pack('H*', $hex);
	}

	public static function generateKey($bytes = 32) {

		// Mixin' it up
		$key = mcrypt_create_iv($bytes/2, MCRYPT_RAND);
		usleep(mt_rand(1,10));
		$key .= mcrypt_create_iv($bytes/2, MCRYPT_RAND);

		return base64_encode($key);
	}
}
