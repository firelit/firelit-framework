<?PHP

namespace Firelit;

class Strings {
	
	public static function validEmail($emailAddy) {
		// Check if an email address is valid
		return filter_var($emailAddy, FILTER_VALIDATE_EMAIL);
	}
	
	public static function addressFix($address) {
		// Fix address case, US address normalization
		
		$address = lower(trim($address));
		if (strlen($address) == 0) return '';
		
		$address = str_replace(array('`','—','–','  ','--'), array("'",'-','-',' ','-'), $address);
		$address = preg_replace("/([a-z]+[\-'])([a-z]{2})/e", "'$1' . ucword('$2')", $address);
		$address = preg_replace("/([0-9#])([a-z])(\b|[0-9])/e", "'$1' . ucword('$2') . '$3'", $address);
		
		$patterns = array('/p\.o\.(\s?)/i',	'/^po\s/i',	'/^po\.(\s?)/i'); 
		$replacew = array('PO ', 						'PO ',			'PO '); 
		$address = preg_replace($patterns, $replacew, $address);
		
		$patterns = array('/\bn(\.?\s?)e(\.?)\s/i',	'/\bn(\.?\s?)e(\.?)$/i',	
											'/\bn(\.?\s?)w(\.?)\s/i',	'/\bn(\.?\s?)w(\.?)$/i',	
											'/\bs(\.?\s?)e(\.?)\s/i',	'/\bs(\.?\s?)e(\.?)$/i',	
											'/\bs(\.?\s?)w(\.?)\s/i',	'/\bs(\.?\s?)w(\.?)$/i',
											'/\br(\.?\s?)r(\.?)\s/i'); 
		$replacew = array('NE ', 'NE', 'NW ', 'NW', 'SE ', 'SE', 'SW ', 'SW', 'RR '); 
		$address = ucword(preg_replace($patterns, $replacew, $address));
		
		return $address;
		
	}

	public static function nameFix($name) {
		// Fix name case, US name normalization
		
		if (preg_match("/\b(Van|De|Di)[A-Z][a-z]+/", $name)) $compName = true; else $compName = false; // Will be all lower case, next
		$name = ucword(lower(trim($name)));
		
		if (strlen($name) == 0) return '';
	
		$name = str_replace(array('`','—','–','  ','--','And '), array("'",'-','-',' ','-','and '), $name); 
		$name = preg_replace("/([A-Za-z]+[\-'])([A-Za-z]{2})/e", "'$1' . ucword('$2')", $name);
		$name = preg_replace("/([a-z])[\+&]([a-z])/e", "'$1' . ' & ' . ucword('$2')", $name);
		$name = preg_replace("/(Mc)([a-z]+)/e", "'$1' . ucword('$2')", $name);
		$name = preg_replace("/(\b)(Ii|Iii|Iv)(\b)/e", "'$1' . upper('$2') . '$3'", $name);
		if ($compName)
			$name = preg_replace("/\b(Van|De|Di)([a-z]+)/e", "'$1' . ucword('$2')", $name); 
			
		return $name;
		
	}

	public static function cleanUTF8(&$input, $lineBreaksOk = true) {
		// Clean input for UTF-8 valid characters
		
		if (is_array($input)) {
		
			foreach ($input as $k => $v) 
				self::cleanUTF8($input[$k], $lineBreaksOk);
		
		} else {
			
			$input = mb_convert_encoding($input, "UTF-8", "UTF-8");
			if ($lineBreaksOk) $input = preg_replace('![\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\x9F]!', '', $input);
			else $input = preg_replace('!\p{C}!u', '', $input);
			
		}
	}

	public static function html($string) {
		// HTML-escaping with UTF-8 support
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}
	
	public static function xml($string) { 
		// XML-escaping
		$string = htmlentities($string);
	  $xml = array('&#34;','&#38;','&#38;','&#60;','&#62;','&#160;','&#161;','&#162;','&#163;','&#164;','&#165;','&#166;','&#167;','&#168;','&#169;','&#170;','&#171;','&#172;','&#173;','&#174;','&#175;','&#176;','&#177;','&#178;','&#179;','&#180;','&#181;','&#182;','&#183;','&#184;','&#185;','&#186;','&#187;','&#188;','&#189;','&#190;','&#191;','&#192;','&#193;','&#194;','&#195;','&#196;','&#197;','&#198;','&#199;','&#200;','&#201;','&#202;','&#203;','&#204;','&#205;','&#206;','&#207;','&#208;','&#209;','&#210;','&#211;','&#212;','&#213;','&#214;','&#215;','&#216;','&#217;','&#218;','&#219;','&#220;','&#221;','&#222;','&#223;','&#224;','&#225;','&#226;','&#227;','&#228;','&#229;','&#230;','&#231;','&#232;','&#233;','&#234;','&#235;','&#236;','&#237;','&#238;','&#239;','&#240;','&#241;','&#242;','&#243;','&#244;','&#245;','&#246;','&#247;','&#248;','&#249;','&#250;','&#251;','&#252;','&#253;','&#254;','&#255;');
	  $html = array('&quot;','&amp;','&amp;','&lt;','&gt;','&nbsp;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
	  $string = str_replace($html, $xml, $string); 
	  $string = str_ireplace($html, $xml, $string); 
	  return $string; 
	}
	
	public static function csv($string) {
		// CSV-escaping for a CSV cell
		$string = str_replace('"', '""', $string); 
		return '"'.utf8_decode($string).'"';
	}

	public static function lower($string) {
		// Multi-byte-safe lower-case
		return mb_strtolower($string, 'UTF-8');
	}
	
	public static function upper($string) {
		// Multi-byte-safe lower-case
		return mb_strtoupper($string, 'UTF-8');
	}
	
	public static function ucword($string) {
		// Multi-byte-safe lower-case
		return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8'); // This is also doing a lower() first, not like ucwords()
	}

}