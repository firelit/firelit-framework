<?PHP

namespace Firelit;

class ApiResponse extends Response {
	
	protected $response = array();
	protected $responseSent = false;
	protected $responseFormat = 'JSON';
	
	public function __construct($responseFormat = 'JSON', $ob = true, $charset = "UTF-8") { 
		// Use parent's output buffer controls
		parent::__construct($ob, $charset);

		// Set appropriate output formats
		if ($responseFormat == 'JSON')
			$this->setContentType('application/json');
		
		$this->responseFormat = strtoupper($responseFormat);

	}
	
	public function setTemplate($template) {
		// Set an output template to be sure minimum required fields are returned (eg, there's always a 'success' property)
		$this->response = array_merge($this->response, $template);	
	}
	
	// Set response data
	public function set($response, $replaceResponse = false) {
		if ($replaceResponse) $this->response = $response;
		else $this->response = array_merge($this->response, $response);	
	}
	
	// Sending the response, if it hasn't already been sent
	public function respond($response = array(), $replaceResponse = false) {
		// Set any final data
		$this->set($response, $replaceResponse);

		// Make sure it only responds once
		if ($this->hasResponded()) return;

		// Clear anything that may have snuck in
		if (self::$outputBuffering)
			$this->cleanBuffer();

		// Format for specific output type
		if ($this->responseFormat == 'JSON') {
			
			echo json_encode($this->response);

		} else {
			throw new \Exception('Invalid response format: '. $this->responseFormat);
		}

		// Indicate the response as already sent
		$this->responseSent = true;
	}
	
	// Already sent response?
	public function hasResponded() {
		return $this->responseSent;
	}

	// Cancel the ApiResponse (allows switch to output of non-api data if needed)
	public function cancel() {
		// Stop buffering
		if (self::$outputBuffering) {
			$this->cleanBuffer();
			$this->endBuffer(false);
		}

		// No longer need a response
		$this->responseSent = true;
	}

	// Most likely called at end of execution, outputs data as needed
	public function __destruct() {

		// Respond, if it hasn't yet
		if (!$this->hasResponded()) {
			$this->respond(array(), false);
		}

		// Parent's destructer returns all data in output buffer
		parent::__destruct();
		
	}
}