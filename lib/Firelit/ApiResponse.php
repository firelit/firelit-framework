<?PHP

namespace Firelit;

class ApiResponse extends Response {
	
	protected $response = array();
	protected $responseSent = false;
	protected $responseFormat = 'JSON';
	
	public function __construct($responseFormat = 'JSON', $ob = true, $charset = "UTF-8") { 
		parent::__construct($ob, $charset);

		if ($responseFormat == 'JSON')
			$this->contentType('application/json');
		
		$this->responseFormat = strtoupper($responseFormat);
	}
	
	public function setTemplate($template) {
		$this->response = array_merge($this->response, $template);	
	}
	
	public function set($response, $replaceResponse = false) {
		if ($replaceResponse) $this->response = $response;
		else $this->response = array_merge($this->response, $response);	
	}
	
	public function respond($response = array(), $replaceResponse = false) {
		$this->set($response, $replaceResponse);

		if ($this->hasResponded()) return;

		if ($this->outputBuffering) {
			$this->cleanBuffer();
		}

		if ($this->responseFormat == 'JSON') {
			
			echo json_encode($this->response);

		} else {
			throw new Exception('Invalid response format: '. $this->responseFormat);
		}

		if ($this->outputBuffering) {
			$this->flushBuffer();
		}

		$this->responseSent = true;
	}
	
	public function hasResponded() {
		return $this->responseSent;
	}

	public function __destruct() {

		if (!$this->hasResponded()) {
			$this->respond(array(), false);
		}

	}
}