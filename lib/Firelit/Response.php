<?PHP

namespace Firelit;

class Response extends Singleton {
	
	protected $callback = false;

	// Static to prevent mutliple, tangled, nested output buffers
	static protected $outputBuffering = true;

	// Static b/c there should really only be one type of response
	static protected $code = 200;
	static protected $charset = "UTF-8";
	static protected $contentType = "text/html";

	// Global config - if to throw exception when headers already sent and update can't be made
	static public $exceptOnHeaders = false;
	
	public function __construct($ob = true, $charset = "UTF-8") { 
		// $ob: Turn output buffering on?
		// $charset: Specify the charset?

		// Set charset
		self::$charset = $charset;

		// UTF-8 output by default
		if (!headers_sent()) 
			mb_http_output(self::$charset);
		elseif (self::$exceptOnHeaders)
			throw new \Exception('Headers already sent. Multi-byte output cannot be enabled.');
		
		// Will not turn off or on if already on
		if ($ob && !self::$outputBuffering) {

			// Ouput buffer by default to prevent unforseen errors from printing to the page,
			// to make possible a special 500 error page if something comes up during processing,
			// to prevent flushing in strange places and partial page loads if a internal processes take too long,
			// and ability to redirect at any time if there is an issue
			
			self::$outputBuffering = $ob;

			// Run output through muli-byte filter to match the above-specified (via mb_http_output) output encoding 
			ob_start("mb_output_handler");
			
		}
		
	}
	
	public function __destruct() {

		if (is_callable($this->callback)) {

			if (self::$outputBuffering) {

				$out = ob_get_contents();

				// Work-around: Can't call anonymous functions that are class properties
				// PHP just looks for a method with that name
				$callback = &$this->callback;
				$callback($out);

				$this->clearBuffer();

				echo $out;

			} else $this->callback();

		}

		$this->endBuffer();

	}

	public function setCallback($function) {
		$this->callback = $function;
	}

	public function contentType($type = false) {
			
		if (headers_sent()) {
			
			if (self::$exceptOnHeaders)
				throw new \Exception('Headers already sent. Content-type cannot be changed.');
			else return;
				
		}
		
		if (!$type) $type = "text/html";
		
		self::$contentType = $type ."; charset=". strtolower(self::$charset);

		if (!self::$outputBuffering)
			header("Content-Type: ". self::$contentType);
		
	}
	
	public function code($code = false) {
		
		if (!$code) return http_response_code();

		if (headers_sent()) {
		
			if (self::$exceptOnHeaders && (http_response_code() != $code))
				throw new \Exception('Headers already sent. HTTP response code cannot be changed.');
			else return;
				
		}
		
		self::$code = $code;

		if (!self::$outputBuffering)
			http_response_code(self::$code);

	}

	public function redirect($path, $type = 302, $end = true) {
		// $type should be one of the following:
		// 301 = Moved permanently
		// 302 = Temporary redirect
		// 303 = Perform GET at new location (instead of POST)
		
		if (headers_sent()) {
			
			if (self::$exceptOnHeaders)
				throw new \Exception('Headers already sent. Redirect cannot be initiated.');
			else return;
				
		}
		
		if (self::$outputBuffering)
			$this->cleanBuffer();

		$this->code($type);
		header('Location: '. $path);
		
		if ($end) exit;
		
	}
	
	public function sendHeaders() {
		// If headers haven't been sent and OB is on (if off, headers sent as they are set)
		if (!headers_sent() && self::$outputBuffering) {

			http_response_code(self::$code);
			header("Content-Type: ". self::$contentType);

		}
	}

	public function flushBuffer() {
		// Send buffer out
		$this->sendHeaders();

		if (self::$outputBuffering)
			ob_flush();
			
	}
	
	public function cleanBuffer() {
		// Empty buffer
		if (self::$outputBuffering)
			ob_clean();
			
	}
	
	public function clearBuffer() {
		// Alias of cleanBuffer()
		$this->cleanBuffer();
		
	}

	public function endBuffer($sendHeaders = true) {
		// Call cleanBuffer first if you don't want anything getting out

		// Sending headers is recommended if ending OB to be sure they make it out before content
		if ($sendHeaders)
			$this->sendHeaders();

		if (self::$outputBuffering)
			ob_end_flush();

		self::$outputBuffering = false;

	}

	public function setCode($code) {
		// Set the HTTP response code
		$this->code($code);

	}

	public function setContentType($type) {
		// Set the HTTP content type
		$this->contentType($type);
		
	}


}


// Backwards compatabilty PHP<5.4 (TODO: REMOVE)
if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {

        if ($code !== NULL) {

			$GLOBALS['http_response_code'] = $code;

            if (headers_sent()) return;

            switch ($code) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default: return;
                break;
            }

            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

            header($protocol . ' ' . $code . ' ' . $text);

        } else {

            $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

        }

        return $code;

    }
}