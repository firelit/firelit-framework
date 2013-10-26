<?PHP

namespace Firelit;

class Session {
	
	private $store, $fetched = false, $cache = array(), $updatesToSave = false;
	
	public function __construct(SessionStore $store) {	
		// Create a session using the given SessionStore object
		
		$this->store = $store;
		
	}
	
	public function __set($name, $val) {
		// Magic sesion value setter 
		
		if (!$this->fetched) 
		  $this->fetch();
		
		$this->cache[$name] = $val;
		$this->updatesToSave = true;
		
	}
	
	public function __unset($name) {
		
		unset($this->cache[$name]);
		$this->updatesToSave = true;
		
	}
	
	public function __get($name) {
		// Magic sesion value getter 
		
		if (!$this->fetched) 
		  $this->fetch();
		
		if (isset($this->cache[$name]))
			return $this->cache[$name];
		
		return null;
		
	}
	
	public function __destruct() {
		if ($this->updatesToSave)
			$this->save();
	}
	
	public function fetch($saveFirst = true) {
		
		if ($saveFirst && $this->fetched && $this->updatesToSave) {
			$this->save();
		}
		
		$this->cache = $this->store->fetch();
		$this->fetched = true;
		$this->updatesToSave = false;
	  
	}
	
	public function save() {
	
		$this->store->store($this->cache);
		
		$this->updatesToSave = false;
		
	}
	
	public function discardUpdates() {
		
		$this->updatesToSave = false;
		
	}
	
	public function flushCache() {
		
		$this->cache = array();
		$this->fetched = false;
		$this->updatesToSave = false;
		
	}
	
	public function destroy() {
		// Remove all data from and traces of the current session
		
		$this->store->destroy();
		
		$this->cache = array();
		$this->updatesToSave = false;
		$this->fetched = true;
		
	}
	
}
