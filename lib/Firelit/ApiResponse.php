<?PHP

namespace Firelit;

class ApiResponse extends Response {
	
	protected $response = array();
	protected $responseSent = false;
	protected $responseFormat = 'JSON';
	protected $apiResponseCallback = false;
	protected $jsonCallbackWrap = false;
	
	/**
	 * Set response data
	 * @param String $responseFormat Response type (e.g., 'JSON')
	 * @param Bool $ob Use the output buffer
	 * @param String $charset The character set to specify in header
	 */
	public function __construct($responseFormat = 'JSON', $ob = true, $charset = 'UTF-8') { 
		// Use parent's output buffer controls
		parent::__construct($ob, $charset);

		$this->responseFormat = strtoupper($responseFormat);

		// Set appropriate output formats
		if ($this->responseFormat == 'JSON')
			$this->setContentType('application/json');
		
	}
	
	/**
	 * Set the template or defaults for response
	 * @param Array $template Template response to send
	 */
	public function setTemplate($template) {
		// Set an output template to be sure minimum required fields are returned (eg, there's always a 'success' property)
		$this->response = array_merge($this->response, $template);	
	}
	
	/**
	 * Set response data
	 * @param Array $response Response to send
	 * @param Bool $replaceResponse Indicates if response should be completely replaced with previous param (instead of merged)
	 */
	public function set($response, $replaceResponse = false) {
		if ($replaceResponse) $this->response = $response;
		else $this->response = array_merge($this->response, $response);	
	}
	
	/**
	 * Sending the response, if it hasn't already been sent
	 * @param Array $response Response to send
	 * @param Bool $replaceResponse Indicates if response should be completely replaced with previous param (instead of merged)
	 */
	public function respond($response = array(), $replaceResponse = false) {
		// Set any final data
		$this->set($response, $replaceResponse);

		// Make sure it only responds once
		if ($this->hasResponded()) return;

		// Clear anything that may have snuck in
		$this->cleanBuffer();

		if (self::$code == 204) {
			// No-body response
			$this->responseSent = true;
			return;
		}

		if (!empty($this->apiResponseCallback) && is_callable($this->apiResponseCallback)) {
			$callback = &$this->apiResponseCallback;
			$callback($this->response);
		}

		// Format for specific output type
		if ($this->responseFormat == 'JSON') {
			
			if ($this->jsonCallbackWrap) echo $this->jsonCallbackWrap . '(';

			echo json_encode($this->response);

			if ($this->jsonCallbackWrap) echo ');';

		} else {
			throw new \Exception('Invalid response format: '. $this->responseFormat);
		}

		// Indicate the response as already sent
		$this->responseSent = true;
	}
	
	/**
	 * Already sent response?
	 * @return Bool
	 */
	public function hasResponded() {
		return $this->responseSent;
	}

	/**
	 * Cancel the ApiResponse (allows switch to output of non-api data if needed)
	 */
	public function cancel() {
		// No longer need a response
		$this->responseSent = true;
	}

	/**
	 * Set a function to be wrapped around JSON response (for JSONP)
	 * @param String $callback The function name
	 */
	public function setJsonCallbackWrap($callback) {

		if (empty($callback)) {
			$this->jsonCallbackWrap = false;
			return;
		}

		if (!is_string($callback))
			throw new \Exception('JSON callback wrap should be a string or false.');

		$this->jsonCallbackWrap = $callback;

	}

	/**
	 * Set a function to be called upon response (or false to cancel)
	 * @param Funcation $callback The function to call with 1 parameter being the data to return (pass by reference to modify)
	 */
	public function setApiCallback($callback) {

		if (empty($callback)) {
			$this->apiResponseCallback = false;
			return;
		}

		if (!is_callable($callback))
			throw new \Exception('Callback should be a function or false.');

		$this->apiResponseCallback = $callback;

	}

	/**
	 * Most likely called at end of execution, outputs data as needed
	 */
	public function __destruct() {

		// Respond, if it hasn't yet
		if (!$this->hasResponded())
			$this->respond(array(), false);

		// Parent's destructer returns all data in output buffer
		parent::__destruct();
		
	}

}