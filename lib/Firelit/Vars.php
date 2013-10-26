<?PHP

namespace Firelit;

class Vars {
	
	private $store;
	
	public function __construct(VarsStore $store) {	
		// Create a session using the given VarsStore object
		
		$this->store = $store;
		
	}
	
	public function __set($name, $val) {
		
		$this->store->set($name, $val);
		
	}
	
	public function __unset($name) {
		
		$this->store->set($name, null);
		
	}
	
	public function __get($name) {
		
		return $this->store->get($name);
		
	}
	
}