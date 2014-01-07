<?PHP

namespace Firelit;

class DatabaseObject {
	
	// To Use: Extend this class and replace the following values
	protected static $tableName = 'ReplaceMe'; // The table name
	protected static $primaryKey = 'id'; // The primary key for table (or false if n/a)

	protected $data = array(); // The object's data
	protected $dirty = array(); // Array of dirty properties that need saving
	protected $new = false; // Indicates a new, unsaved object
	
	protected static $query = false;

	public function __construct($data = false) {

		// If it doesn't come pre-loaded, it's new
		// (Pre-loading made possible by PDOStatement::fetchObject)
		if (!sizeof($this->data)) $this->new = true;

		$this->dirty = array(); // Reset in case pre-loaded

		if (is_array($data))
			$this->data = $data;

	}

	public static function setQueryObject(Query $query) {
		// Used for testing
		static::$query = $query;
	}

	public function save() {

		if (!$this->new && !sizeof($this->dirty)) return;

		if (static::$query) $q = static::$query;
		else $q = new Query();
		
		if ($this->new) {

			$q->insert(static::$tableName, $this->data);

			// If new, set data id from autoincrement id
			if (static::$primaryKey)
				$this->data[static::$primaryKey] = $q->getNewId();

		} else {

			if (!static::$primaryKey) 
				throw new \Exception('Cannot perform update without a primary key.');

			if (!isset($this->data[static::$primaryKey]))
				throw new \Exception('Cannot perform update without primary key set.');

			$updateData = array(); 

			foreach ($this->dirty as $key) {
				if (isset($this->data[$key]))
					$updateData[$key] = $this->data[$key];
			}

			if (isset($updateData[static::$primaryKey]))
				throw new \Exception('Cannot perform update on primary key (it was marked dirty).');

			$q->update(static::$tableName, $updateData, "WHERE `". static::$primaryKey ."`='". $this->data[static::$primaryKey] ."' LIMIT 1");
			
		}
		
		$this->new = false;
		$this->dirty = array();

	}

	public function setNew() {
		$this->new = true;
		$this->dirty = array();
	}

	public function setNotNew() {
		$this->new = false;
	}

	public function isNew() {
		return $this->new;
	}

	public function getDirty() {
		return $this->dirty;
	}

	public function __get($var) {

		if (isset($this->data[$var])) return $this->data[$var];
		else return null;

	}

	public function __set($var, $val) {

		$this->data[$var] = $val;

		if (!$this->new && !in_array($var, $this->dirty))
			$this->dirty[] = $var;

	}

	public function __clone() {
		
		if (static::$primaryKey)
			unset($this->data[static::$primaryKey]);
		
		$this->new = true;
		$this->dirty = array();

	}

	public function delete() {

		if (!static::$primaryKey) 
			throw new \Exception('Cannot perform delete without a primary key.');

		$this->data = array();
		$this->dirty = array();

		if ($this->new) return;
		
		$sql = "DELETE FROM `". static::$tableName ."` WHERE `". static::$primaryKey ."`=:id LIMIT 1";

		if (static::$query) $q = static::$query;
		else $q = new Query();

		$q->query($sql, array( ':id' => $this->data[static::$primaryKey] ));

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