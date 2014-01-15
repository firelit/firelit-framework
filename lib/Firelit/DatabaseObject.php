<?PHP

namespace Firelit;

class DatabaseObject {
	
	// To Use: Extend this class and replace the following values
	protected static $tableName = 'ReplaceMe'; // The table name
	protected static $primaryKey = 'id'; // The primary key for table (or false if n/a)

	protected $_data = array(); // The object's data
	protected $_dirty = array(); // Array of dirty properties that need saving
	protected $_new = false; // Indicates a new, unsaved object
	
	protected static $query = false;

	public function __construct($data = false) {

		// If it doesn't come pre-loaded, it's new
		// (Pre-loading made possible by PDOStatement::fetchObject)
		if (!sizeof($this->_data)) $this->_new = true;

		$this->_dirty = array(); // Reset in case pre-loaded

		if (is_array($data))
			$this->_data = $data;

	}

	public static function setQueryObject(Query $query) {
		// Used for testing
		static::$query = $query;
	}

	public function save() {

		if (!$this->_new && !sizeof($this->_dirty)) return;

		if (static::$query) $q = static::$query;
		else $q = new Query();
		
		if ($this->_new) {

			$q->insert(static::$tableName, $this->_data);

			// If new, set data id from autoincrement id
			if (static::$primaryKey)
				$this->_data[static::$primaryKey] = $q->getNewId();

		} else {

			if (!static::$primaryKey) 
				throw new \Exception('Cannot perform update without a primary key.');

			if (!isset($this->_data[static::$primaryKey]))
				throw new \Exception('Cannot perform update without primary key set.');

			$updateData = array(); 

			foreach ($this->_dirty as $key) {
				if (isset($this->_data[$key]))
					$updateData[$key] = $this->_data[$key];
			}

			if (isset($updateData[static::$primaryKey]))
				throw new \Exception('Cannot perform update on primary key (it was marked dirty).');

			$q->update(static::$tableName, $updateData, "WHERE `". static::$primaryKey ."`='". $this->_data[static::$primaryKey] ."' LIMIT 1");
			
		}
		
		$this->_new = false;
		$this->_dirty = array();

	}

	public function setNew() {
		$this->_new = true;
		$this->_dirty = array();
	}

	public function setNotNew() {
		$this->_new = false;
	}

	public function isNew() {
		return $this->_new;
	}

	public function getDirty() {
		return $this->_dirty;
	}

	public function __get($var) {

		if (isset($this->_data[$var])) return $this->_data[$var];
		else return null;

	}

	public function __set($var, $val) {

		$this->_data[$var] = $val;

		if (!$this->_new && !in_array($var, $this->_dirty))
			$this->_dirty[] = $var;

	}

	public function __clone() {
		
		if (static::$primaryKey)
			unset($this->_data[static::$primaryKey]);
		
		$this->_new = true;
		$this->_dirty = array();

	}

	public function delete() {

		if (!static::$primaryKey) 
			throw new \Exception('Cannot perform delete without a primary key.');

		$this->_data = array();
		$this->_dirty = array();

		if ($this->_new) return;
		
		$sql = "DELETE FROM `". static::$tableName ."` WHERE `". static::$primaryKey ."`=:id LIMIT 1";

		if (static::$query) $q = static::$query;
		else $q = new Query();

		$q->query($sql, array( ':id' => $this->_data[static::$primaryKey] ));

	}

	public static function create($data) {

		$class = get_called_class();

		$do = new $class($data);
		$do->save();

		return $do;

	}

	public static function find($id) {

		if (!static::$primaryKey) 
			throw new \Exception('Cannot perform find without a primary key.');

		$sql = "SELECT * FROM `". static::$tableName ."` WHERE `". static::$primaryKey ."`=:id LIMIT 1";

		if (static::$query) $q = static::$query;
		else $q = new Query();

		$q->query($sql, array( ':id' => $id ));

		return $q->getObject(get_called_class());

	}

}