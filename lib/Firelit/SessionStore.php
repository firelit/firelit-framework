<?PHP

namespace Firelit;

abstract class SessionStore extends InitExtendable {
	
	// Set an array of values to be stored (treat $expireSeconds as garbage collection trigger more than session limiter)
	abstract public function store($array);
	
	// Fetch all values for a session
	abstract public function fetch();
	
	// Destroy a session and all stored values
	abstract public function destroy();
	
}