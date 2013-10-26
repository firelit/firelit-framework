<?PHP

namespace Firelit;

class Crypto {
	
	static public $config = array(
		'password' => array(
			'blowfish' => true
		)
	);
	
	function __construct() { }
	
	public static function config($config) {
		self::$config = array_merge(self::$config, $config);	
	}
	
	public static function getIv() {
		return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
	}
	
	public static function encrypt($text, $key, $iv) { 
		return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, base64_decode($iv)))); 
	} 
	
	public static function decrypt($text, $key, $iv) { 
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($text), MCRYPT_MODE_ECB, base64_decode($iv))); 
	} 

	public static function password($pswd, $salt = false) {
		
		if (self::$config['password']['blowfish']) {
			
			if (!$salt || !strlen($salt)) $salt = '$2a$08$'. self::mineSalt(21) .'$'; // $2a$ = blowfish, $08$ = cost
			
			$pswd = crypt($pswd, $salt);
			$pswd = str_replace(substr($salt, 0, -1), '', $pswd);
			
		} else {
			
			if (!$salt || !strlen($salt)) $salt = self::mineSalt(21);
			
			for ($i = 0; $i < 5; $i++) {
				$pswd = base64_encode(hash('sha256', $salt . $pswd, true));
			}
			
		}
		
		return array($pswd, $salt);
		
	}
	
	public static function mineSalt($len) {
		
	  $symArray = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	  $symArrayLen = strlen($symArray);
	  
	  $key = preg_replace('/[^0-9a-zA-Z]+/', '', base64_encode(hash('sha256', microtime() . $_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'], true))); 
	  $key = substr($key, mt_rand(0, 5), round($len / 2));
	  
	  while (strlen($key) < $len)
	  	$key .= substr($symArray, mt_rand() % $symArrayLen, 1); 
	
		return $key;
		
	}
}
