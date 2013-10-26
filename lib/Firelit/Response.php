<?PHP

namespace Firelit;

class Response extends InitExtendable {
	
	protected $code = 200;
	protected $outputBuffering = true;
	protected $charset;
	
	static public $exceptOnHeaders = false;
	
	public function __construct($ob = true, $charset = "UTF-8") { 
		// $ob: Turn output buffering on?
		// $charset: Specify the charset?
		$this->outputBuffering = $ob;
		$this->charset = $charset;
		
		// UTF-8 output by default
		if (!headers_sent())
			mb_http_output($this->charset);
		elseif (self::$exceptOnHeaders)
			throw new \Exception('Headers already sent. Multi-byte output cannot be enabled.');
		
		if ($ob) {
			// Ouput buffer by default to prevent unforseen errors from printing to the page,
			// to make possible a special 500 error page if something comes up during processing,
			// to prevent flushing in strange places and partial page loads if a internal processes take too long,
			// and ability to redirect at any time if there is an issue
			
			// Run output through muli-byte filter to match the above-specified output encoding
			
			ob_start("mb_output_handler");
			
		}
		
	}
	
	public function contentType($type = false) {
			
		if (headers_sent()) {
			
			if (self::$exceptOnHeaders)
				throw new \Exception('Headers already sent. Content-type cannot be changed.');
			else return;
				
		}
		
		if (!$type) $type = "text/html";
		
		header("Content-Type: ". $type ."; charset=". strtolower($this->charset));
		
	}
	
	public function code($code) {
		
		if (headers_sent()) {
		
			if (self::$exceptOnHeaders && (http_response_code() != $code))
				throw new \Exception('Headers already sent. HTTP response code cannot be changed.');
			else return;
				
		}
		
		$this->code = $code;
		http_response_code($code);
		
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
		
		$this->code($type);
		header('Location: '. $path);
		
		if ($this->outputBuffering)
			ob_end_clean();
		
		if ($end) exit;
		
	}
	
	public function flushBuffer() {
	
		if ($this->outputBuffering)
			ob_flush();
			
	}
	
	public function cleanBuffer() {
	
		if ($this->outputBuffering)
			ob_clean();
			
	}
	
	public function endBuffer() {
		// Call cleanBuffer first if you don't want anything getting out
		if ($this->outputBuffering)
			ob_end_flush();
			
	}
}